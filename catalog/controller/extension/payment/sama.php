<?php

/**
 * @author : isabad.com
 * @since v1.0.0
 * This is A main Class Of GateWay Plugin Written By Seyed Hojat Hosseini
 */
class ControllerExtensionPaymentSama extends Controller
{
    public function index()
    {
        $this->load->language('extension/payment/sama');

        $data1['text_connect'] = $this->language->get('text_connect');
        $data1['text_loading'] = $this->language->get('text_loading');
        $data1['text_wait'] = $this->language->get('text_wait');

        $data1['button_confirm'] = $this->language->get('button_confirm');

        return $this->load->view('extension/payment/sama', $data1);
    }

    public function confirm()
    {
        $this->load->language('extension/payment/sama');

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $amount = $this->correctAmount($order_info);

        $data1['return'] = $this->url->link('checkout/success', '', true);
        $MerchantID = $this->config->get('payment_sama_pin');
        $Amount = $amount;
        $Mobile = isset($order_info['fax']) ? $order_info['fax'] : $order_info['telephone'];
        $CallbackURL = $this->url->link('extension/payment/sama/callback', 'order_id=' . $data1['order_id'], true);
        $cid = md5($order_info['order_id'] . rand(11111111 , 999999999999999));
        $this->session->data['sama_cid'] = $cid;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://app.sama.ir/api/stores/services/deposits/guaranteed/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Api-Key ' . $MerchantID,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{"price": "' . $Amount * 10 . '","client_id": "' .  $cid . '","buyer_phone": "' . $Mobile . '","callback_url": "' . $CallbackURL . '"}');
        $result = json_decode(curl_exec($ch));
        curl_close($ch);
        if (!$result) {
            $json = array();
            $json['error'] = $this->language->get('error_cant_connect');
        } elseif ($result->web_view_link != null) {
            $data1['action'] = $result->web_view_link;
            $json['success'] = $data1['action'];
        } else {
            $json['cid']  = $cid;
            $json['result']  = $result;
            $json['error'] = 'خطایی در سامانه پرداخت رخ داده است کمی بعد تلاش کنید.';
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function correctAmount($order_info)
    {
        $amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
        $amount = round($amount);
        $amount = $this->currency->convert($amount, $order_info['currency_code'], "TOM");
        return (int)$amount;
    }

    private function checkState($status)
    {
        $json = array();
        $json['error'] = $this->language->get('error_status_undefined');

        if ($this->language->get('error_status_' . $status) != 'error_status_' . $status) {
            $json['error'] = $this->language->get('error_status_' . $status);
        }

        return $json;
    }

    public function callback()
    {
        if ($this->session->data['payment_method']['code'] == 'sama') {
            $this->load->language('extension/payment/sama');

            $this->document->setTitle($this->language->get('text_title'));

            $data1['heading_title'] = $this->language->get('text_title');
            $data1['results'] = "";

            $data1['breadcrumbs'] = array();
            $data1['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home', '', true)
            );
            $data1['breadcrumbs'][] = array(
                'text' => $this->language->get('text_title'),
                'href' => $this->url->link('extension/payment/sama/callback', '', true)
            );

            try {
                if ($this->request->get['request_id'] == null)
                    throw new Exception($this->language->get('error_verify'));

                if (isset($this->session->data['order_id'])) {
                    $order_id = $this->session->data['order_id'];
                } else {
                    $order_id = 0;
                }

                $this->load->model('checkout/order');
                $order_info = $this->model_checkout_order->getOrder($order_id);

                if (!$order_info)
                    throw new Exception($this->language->get('error_order_id'));

                $client_id = $this->session->data['sama_cid'];
                $request_id = $this->request->get['request_id'];
                $verifyResult = $this->verifyPayment($request_id , $client_id );
                if (!$verifyResult)
                    throw new Exception($this->language->get('error_connect_verify'));

                switch (array_keys($verifyResult)[0]) {
                    case 'RefID': // success
                        $comment = $this->language->get('text_results') . $verifyResult['RefID'];
                        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_sama_order_status_id'), $comment, true);

                        $data1['error_warning'] = NULL;
                        $data1['results'] = $verifyResult['RefID'];
                        $data1['button_continue'] = $this->language->get('button_complete');
                        $data1['continue'] = $this->url->link('checkout/success');
                        break;

                    case 'Status': // error with error status
                        throw new Exception($this->checkState($verifyResult['Status'])['error']);
                        break;
                }

            } catch (Exception $e) {
                $data1['error_warning'] = $e->getMessage();
                $data1['button_continue'] = $this->language->get('button_view_cart');
                $data1['continue'] = $this->url->link('checkout/cart');
            }

            $data1['column_left'] = $this->load->controller('common/column_left');
            $data1['column_right'] = $this->load->controller('common/column_right');
            $data1['content_top'] = $this->load->controller('common/content_top');
            $data1['content_bottom'] = $this->load->controller('common/content_bottom');
            $data1['footer'] = $this->load->controller('common/footer');
            $data1['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('extension/payment/sama_confirm', $data1));
        }
    }
    private function verifyPayment($request_id, $client_id)
    {
        $MerchantID = $this->config->get('payment_sama_pin');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://app.sama.ir/api/stores/services/deposits/guaranteed/payment/verify/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Api-Key '.$MerchantID,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{"request_id":"'.$request_id.'","client_id":"'.$client_id.'"}');

        $response = curl_exec($ch);

        curl_close($ch);

        $result = json_decode($response);
        if (!$result) {
            return false;
        } elseif ($result->is_paid) {
            return ['RefID' => $result->payment->reference_number];
        } else {
            return ['Status' => $result->code];
        }
    }

}

?>
