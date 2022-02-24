<?php
class ModelExtensionPaymentSadad extends Model
{
	public function getMethod($address)
	{
		// load language file
		$this->load->language('extension/payment/sadad');

		if ($this->config->get('payment_sadad_status')) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'sadad',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_sadad_sort_order')
			);
		}

		return $method_data;
	}
}
