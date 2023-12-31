<?php
class ControllerExtensionPaymentSama extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/sama');

		$this->document->setTitle(strip_tags($this->language->get('heading_title')));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_sama', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['pin'])) {
			$data['error_pin'] = $this->error['pin'];
		} else {
			$data['error_pin'] = '';
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
			'href' => $this->url->link('extension/payment/sama', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/sama', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_sama_pin'])) {
			$data['payment_sama_pin'] = $this->request->post['payment_sama_pin'];
		} else {
			$data['payment_sama_pin'] = $this->config->get('payment_sama_pin');
		}

		if (isset($this->request->post['payment_sama_host_type'])) {
			$data['payment_sama_host_type'] = $this->request->post['payment_sama_host_type'];
		} else {
			$data['payment_sama_host_type'] = $this->config->get('payment_sama_host_type');
		}

		if (isset($this->request->post['payment_sama_order_status_id'])) {
			$data['payment_sama_order_status_id'] = $this->request->post['payment_sama_order_status_id'];
		} else {
			$data['payment_sama_order_status_id'] = $this->config->get('payment_sama_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_sama_status'])) {
			$data['payment_sama_status'] = $this->request->post['payment_sama_status'];
		} else {
			$data['payment_sama_status'] = $this->config->get('payment_sama_status');
		}

		if (isset($this->request->post['payment_sama_sort_order'])) {
			$data['payment_sama_sort_order'] = $this->request->post['payment_sama_sort_order'];
		} else {
			$data['payment_sama_sort_order'] = $this->config->get('payment_sama_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/sama', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/sama')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->request->post['payment_sama_pin']) {
			$this->error['pin'] = $this->language->get('error_pin');
		}

		return !$this->error;
	}
}
?>