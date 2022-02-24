<?php
class ControllerExtensionPaymentSadad extends Controller
{
	public function index()
	{
		// load language file
		$this->load->language('extension/payment/sadad');

		$data['text_connect'] = $this->language->get('text_connect');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['text_wait'] = $this->language->get('text_wait');

		$data['button_confirm'] = $this->language->get('button_confirm');

		return $this->load->view('extension/payment/sadad', $data);
	}

	public function confirm()
	{
		// load language file
		$this->load->language('extension/payment/sadad');

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$amount = $this->correctAmount($order_info);

		$data['return'] = $this->url->link('checkout/success', '', true);
		$data['cancel_return'] = $this->url->link('checkout/payment', '', true);
		$data['back'] = $this->url->link('checkout/payment', '', true);

		$MerchantId = $this->config->get('payment_sadad_merchant_id');  	//Required
		$TerminalId = $this->config->get('payment_sadad_terminal_id');  	//Required
		$terminal_key = $this->config->get('payment_sadad_terminal_key');  	//Required

		$OrderId = $this->session->data['order_id'];

		$data['order_id'] = $OrderId;

		$LocalDateTime = date("m/d/Y g:i:s a");

		$ReturnUrl = $this->url->link('extension/payment/sadad/callback', '', true);  // Required

		$SignData = $this->encrypt_function($TerminalId . ';' . $OrderId . ';' . $amount, $terminal_key);

		$data = array(
			'MerchantID' => $MerchantId,
			'TerminalId' => $TerminalId,
			'Amount' => $amount,
			'OrderId' => $OrderId,
			'LocalDateTime' => $LocalDateTime,
			'ReturnUrl' => $ReturnUrl,
			'SignData' => $SignData,
		);

		$result = $this->call_api('https://sadad.shaparak.ir/vpg/api/v0/Request/PaymentRequest', $data);

		if (!$result) {
			$json = array();
			$json['error'] = $this->language->get('error_cant_connect');
		} else {
			if ($result->ResCode == 0) {
				$Token = $result->Token;
				$data['action'] = "https://sadad.shaparak.ir/VPG/Purchase?Token=$Token";
				$json['success'] = $data['action'];
			} else {
				$json = array();
				$json['error'] = $result->Description;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function callback()
	{
		if ($this->session->data['payment_method']['code'] == 'sadad') {

			// load language file
			$this->load->language('extension/payment/sadad');

			$this->document->setTitle($this->language->get('text_title'));

			$data['heading_title'] = $this->language->get('text_title');
			$data['results'] = "";

			$data['breadcrumbs'] = array();
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', '', true)
			);
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_title'),
				'href' => $this->url->link('extension/payment/sadad/callback', '', true)
			);

			try {
				if ($this->request->post['OrderId'] && $this->request->post['token']) {
					if ($this->request->post['ResCode'] == "0") {
						$Token = $this->request->post['token'];

						if (isset($this->session->data['order_id'])) {
							$OrderId = $this->session->data['order_id'];
						} else {
							$OrderId = 0;
						}

						$this->load->model('checkout/order');
						$order_info = $this->model_checkout_order->getOrder($OrderId);

						if (!$order_info)
							throw new Exception($this->language->get('error_order_id'));

						// verify payment
						$verifyData = array(
							'Token' => $Token,
							'SignData' => $this->encrypt_function($Token, $this->config->get('payment_sadad_terminal_key')),
						);

						$result = $this->call_api('https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify', $verifyData);

						if (!$result) {
							$data['error_warning'] = $this->language->get('error_payment');
						} else {
							if ($result->ResCode != -1 && $result->ResCode == 0) {
								$comment = $this->language->get('text_transaction') . $result->SystemTraceNo;
								$comment .= '<br/>' . $this->language->get('text_transaction_reference') . $result->RetrivalRefNo;

								$this->model_checkout_order->addOrderHistory($OrderId, $this->config->get('payment_sadad_order_status_id'), $comment, true);

								$data['error_warning'] = NULL;

								$data['system_trace_no'] = $result->SystemTraceNo;
								$data['retrival_ref_no'] = $result->RetrivalRefNo;

								$data['button_continue'] = $this->language->get('button_complete');
								$data['continue'] = $this->url->link('checkout/success');
							} else {
								$data['error_warning'] = $this->language->get('error_payment');
							}
						}
					} else {
						$data['error_warning'] = $this->language->get('error_payment');
					}
				} else {
					$data['error_warning'] = $this->language->get('error_data');
				}
			} catch (Exception $e) {
				$data['error_warning'] = $e->getMessage();
				$data['button_continue'] = $this->language->get('button_view_cart');
				$data['continue'] = $this->url->link('checkout/cart');
			}


			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('extension/payment/sadad_confirm', $data));
		}
	}

	private function correctAmount($order_info)
	{
		$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$currency = $order_info['currency_code'];
		$rate = 0;
		if ($currency == 'RLS') {
			$rate = 1;
		} elseif ($currency == 'TOM') {
			$rate = 10;
		}
		return $amount * $rate;
	}

	private function encrypt_function($data, $key)
	{
		$key = base64_decode($key);
		$ciphertext = OpenSSL_encrypt($data, "DES-EDE3", $key, OPENSSL_RAW_DATA);
		return base64_encode($ciphertext);
	}

	private function call_api($url, $data = false)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json; charset=utf-8'));
		curl_setopt($ch, CURLOPT_POST, 1);
		if ($data) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$result = curl_exec($ch);
		curl_close($ch);
		return !empty($result) ? json_decode($result) : false;
	}
}
