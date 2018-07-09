<?php
namespace ConsoleFinance;

class Client
{
  private $http;

  protected $endpoint = 'https://console.finance/api/pay';

  private $api_key;
  private $token;
  private $test = false;
  private $ip;

  protected $status = 'READY';
  protected $status_msg;
  protected $status_msg_dev;
  protected $status_msgs = [
    'UNEXPECTED' => 'Beklenmedik bir hata oluştu.',
    'READY' => 'İstemci isteklerinizi işlemek için hazır bekliyor.',
    'BAD_RESPONSE' => 'Beklenmedik bir hata oluştu.',
    'FAILED' => 'Ödeme işlemi başarısız oldu.'
  ];

  private $payee;
  private $payer;
  private $payment;
  private $callback_url;

  function __construct() {
    $this->http = new \GuzzleHttp\Client();
    $this->ip = $this->get_ip();
  }

  function set_api_key($key) {
    $this->api_key = $key;
  }

  function set_token($token) {
    $this->token = $token;
  }

  protected function test() {
    $this->test = true;
  }

  function set_state($state) {
    $state = json_decode($state, true);
    $this->payee = $state['payee'];
    $this->payer = $state['payer'];
    $this->payment = [
      'amount' => $state['amount'],
      'amount_with_interest_rate' => $state['amountWithInterestRate'],
      'currency' => $state['currencyCode'],
      'payment_channel' => $state['paymentChannel'],
      'campaign' => $state['campaignId'],
      'installments' => $state['installments']
    ];
    $this->callback_url = $state['callbackURL'];
    if ($state['test'] !== false) $this->test();
  }

  function send_payment_request() {
    $response = $this->http->request('POST', $this->endpoint, [
      'headers' => [
        'Authorization' => 'Bearer ' . $this->api_key
      ],
      'json' => [
        'token' => $this->token,
        'payee' => $this->payee,
        'payer' => $this->payer,
        'payment' => $this->payment,
        'callback_url' => $this->callback_url,
        'test' => $this->test,
        'ip' => $this->ip
      ]
    ]);

    $body = (string) $response->getBody();

    $json = [];
    try {
      $json = json_decode($body, true);
    } catch (\Exception $e) {
      $this->status = 'BAD_RESPONSE';
      $this->status_msg_dev = $e->getMessage();
      return false;
    }

    if (isset($json['redirect_url'])) {
      $this->status = 'REDIRECTED';
      header("Location: " . $json['redirect_url']);
      return false;
    }

    $result = [
      'consolefin_status' => !empty($json['code']) ? $json['code'] : 'FAILED',
      'consolefin_msg' => !empty($json['msg']) ? $json['msg'] : $this->status_msgs['UNEXPECTED'],
      'consolefin_msg_dev' => !empty($json['msg_dev']) ? $json['msg_dev'] : '',
      'consolefin_transaction_uuid' => isset($json['transaction_uuid']) ? $json['transaction_uuid'] : false
    ];

    $this->redirect_and_send($result);

    return false;
  }

  protected function redirect_and_send($obj=[]) {
    $inputs = '';
    foreach ($obj as $key => $value) {
      $inputs .= '<input type="hidden" name="'.$key.'" value="'.$value.'">';
    }

    if (empty($this->callback_url)) {
      echo '';
      exit;
    }

    $html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><script type="text/javascript" language="javascript">function redirectAndSend() { document.callbackform.submit(); }</script></head><body onLoad="javascript:redirectAndSend()"><form action="'.$this->callback_url.'" method="post" name="callbackform">'.$inputs.'<noscript><center>Yönlendiriliyorsunuz...<br><input type="submit" name="submit" value="Eğer Yönlendirilmediyseniz Buraya Tıklayın" id="btnSbmt"></center></noscript></form></body></html>';

    header('Content-type: text/html');

    echo $html;

    exit;

    return false;
  }

  protected function get_ip() {
    if (isset($_SERVER)) {
  		if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) return $_SERVER["HTTP_X_FORWARDED_FOR"];
  		if (isset($_SERVER["HTTP_CLIENT_IP"])) return $_SERVER["HTTP_CLIENT_IP"];
  		return $_SERVER["REMOTE_ADDR"];
  	}
  	if (getenv('HTTP_X_FORWARDED_FOR')) return getenv('HTTP_X_FORWARDED_FOR');
  	if (getenv('HTTP_CLIENT_IP')) return getenv('HTTP_CLIENT_IP');
  	return getenv('REMOTE_ADDR');
  }

  function get_status() {
    $status = $this->status;
    $msgs = $this->status_msgs;
    $msg = isset($msgs[$status]) ? $msgs[$status] : $msgs['UNEXPECTED'];
    return [
      'code' => $status,
      'msg' => !empty($this->status_msg) ? $this->status_msg : $msg,
      'msg_dev' => $this->status_msg_dev
    ];
  }
}
?>
