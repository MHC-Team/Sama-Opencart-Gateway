<?php 
class ModelExtensionPaymentSama extends Model {
  	public function getMethod($address) {
		$this->load->language('extension/payment/sama');

		if ($this->config->get('payment_sama_status')) {
      		$status = true;
      	} else {
			$status = false;
		}

		$method_data = array();
		
		if ($status) {
      		$method_data = array( 
        		'code'       => 'sama',
        		'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_sama_sort_order')
      		);
    	}
		
    	return $method_data;
  	}
}
?>