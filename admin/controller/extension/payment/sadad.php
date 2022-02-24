<?php
class ControllerExtensionPaymentSadad extends Controller {
	private $error = array();

	public function index() {
		// load language file
		$this->load->language('extension/payment/sadad');

		// set setting title
		$this->document->setTitle(strip_tags($this->language->get('heading_title')));

		// load setting model
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_sadad', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		// check warning error
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		// check merchant id error
		if (isset($this->error['merchant_id'])) {
			$data['error_merchant_id'] = $this->error['merchant_id'];
		} else {
			$data['error_merchant_id'] = '';
		}

		// check terminal id error
		if (isset($this->error['terminal_id'])) {
			$data['error_terminal_id'] = $this->error['terminal_id'];
		} else {
			$data['error_terminal_id'] = '';
		}

		// check terminal key error
		if (isset($this->error['terminal_key'])) {
			$data['error_terminal_key'] = $this->error['terminal_key'];
		} else {
			$data['error_terminal_key'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/sadad', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/sadad', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);


        if (isset($this->request->post['payment_sadad_merchant_id'])) {
            $data['payment_sadad_merchant_id'] = $this->request->post['payment_sadad_merchant_id'];
        } else {
            $data['payment_sadad_merchant_id'] = $this->config->get('payment_sadad_merchant_id');
        }

        if (isset($this->request->post['payment_sadad_terminal_id'])) {
            $data['payment_sadad_terminal_id'] = $this->request->post['payment_sadad_terminal_id'];
        } else {
            $data['payment_sadad_terminal_id'] = $this->config->get('payment_sadad_terminal_id');
        }

        if (isset($this->request->post['payment_sadad_terminal_key'])) {
            $data['payment_sadad_terminal_key'] = $this->request->post['payment_sadad_terminal_key'];
        } else {
            $data['payment_sadad_terminal_key'] = $this->config->get('payment_sadad_terminal_key');
        }

        if (isset($this->request->post['payment_sadad_order_status_id'])) {
            $data['payment_sadad_order_status_id'] = $this->request->post['payment_sadad_order_status_id'];
        } else {
            $data['payment_sadad_order_status_id'] = $this->config->get('payment_sadad_order_status_id');
        }

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_sadad_status'])) {
			$data['payment_sadad_status'] = $this->request->post['payment_sadad_status'];
		} else {
			$data['payment_sadad_status'] = $this->config->get('payment_sadad_status');
		}

		if (isset($this->request->post['payment_sadad_sort_order'])) {
			$data['payment_sadad_sort_order'] = $this->request->post['payment_sadad_sort_order'];
		} else {
			$data['payment_sadad_sort_order'] = $this->config->get('payment_sadad_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/sadad', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/sadad')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->request->post['payment_sadad_merchant_id']) {
			$this->error['merchant_id'] = $this->language->get('error_merchant_id');
		}
		if (!$this->request->post['payment_sadad_terminal_id']) {
			$this->error['terminal_id'] = $this->language->get('error_terminal_id');
		}
		if (!$this->request->post['payment_sadad_terminal_key']) {
			$this->error['terminal_key'] = $this->language->get('error_terminal_key');
		}

		return !$this->error;
	}
}
?>