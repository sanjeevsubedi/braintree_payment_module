<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * This Model class handles the vendor field related functions.
 * 
 * @author Sujan Poudel
 * @version 1.0
 * @date Oct 01, 2013
 * @copyright Copyright (c) 2013, neolinx.com.np
 */
class Payment_model extends CI_Model {

    private $table_name = 'transaction_history';


public function get_origin_country() {

$taxamo = new Taxamo(new APIClient(TAXAMO_PRIVATE_KEY, TAXAMO_API_URL));
/*$country_of_origin = $taxamo->locateMyIP();*/

$origin_ip_address = $_SERVER['REMOTE_ADDR'];
$country_of_origin = $taxamo->locateGivenIP($origin_ip_address);

return $country_of_origin;
/*
object(LocateMyIPOut)#27 (3) {
["remote_addr"]=>
string(15) "113.199.146.114"
["country_code"]=>
string(2) "NP"
["country"]=>
object(Country)#28 (10) {
["ccn3"]=>
string(3) "524"
["name"]=>
string(5) "Nepal"
["code"]=>
string(2) "NP"
["code_long"]=>
string(3) "NPL"
["cca2"]=>
string(2) "NP"
["callingCode"]=>
array(1) {
[0]=>
string(3) "977"
}
["cca3"]=>
string(3) "NPL"
["tax_number_country_code"]=>
NULL
["codenum"]=>
string(3) "524"
["tax_supported"]=>
bool(false)
}
}
*/

}


    /*public function verify_vatnumber($country_code,$tax_number) {
        
        $taxamo = new Taxamo(new APIClient(TAXAMO_PRIVATE_KEY, TAXAMO_API_URL));

        $country_of_origin = $taxamo->locateMyIP();

        $tax_info = $taxamo->validateTaxNumber($country_code, $tax_number); 
        return  $tax_info; 
        
    }*/

    public function getCountries_for_ajax() {
        $taxamo = new Taxamo(new APIClient(TAXAMO_PRIVATE_KEY, TAXAMO_API_URL));
        $countries = $taxamo->getCountriesDict();
        return $countries->dictionary;
    }

    public function getCountries() {
        /*$taxamo = new Taxamo(new APIClient(TAXAMO_PRIVATE_KEY, TAXAMO_API_URL));
        $countries = $taxamo->getCountriesDict();
        return $countries->dictionary;*/

        // getting country list from database
        $this->db->order_by('country_name');
        $query = $this->db->get('taxamo_countries');
        return $query->result();
    }

    public function getTaxInfo($tax_no = null, $country_code = null) {
    $taxamo = new Taxamo(new APIClient(TAXAMO_PRIVATE_KEY, TAXAMO_API_URL));
    
    $transaction_line1 = new Input_transaction_line();
    $transaction_line1->amount = SUBSCRIPTION_AMT;
    $transaction_line1->custom_id = 'line1';
    $transaction_line1->product_type = 'e-service';
    $transaction_line1->description = 'subscription charge';


    $transaction = new Input_transaction();
   
    $transaction->currency_code = CURRENCY;
    //$transaction->buyer_ip = '85.214.135.223';
    $transaction->force_country_code = $country_code; //needed for user without vat number
    $transaction->buyer_tax_number = $tax_no;
    $transaction->transaction_lines = array($transaction_line1);

    try{
        $resp = $taxamo->calculateTax(array('transaction' => $transaction));
        return $resp;
    }catch(TaxamoValidationException $ex) {
        return $ex;
    }
    
    }


    /**
     * insert payment details
     * @param associative array $data     
     * return integer
     */
    public function save($data) {
        $this->db->insert($this->table_name, $data);
        return $this->db->insert_id();
    }


    /**
     * insert payment details of braintree
     * @param associative array $data     
     * return integer
     */
    public function save_braintree_transaction($data) {
        $this->db->insert('braintree_transaction', $data);
        return $this->db->insert_id();
    }

    /**
     * insert payment details of braintree
     * @param associative array $data     
     * return integer
     */
    public function get_braintree_transaction($customer_id='',$start_date='',$end_date='') {
        

        $this->db->select("*");
        $this->db->from('braintree_transaction');
        if($start_date!='' && $end_date!=''){
            
            $this->db->where('createdAt >=',$start_date); 
            $this->db->where('createdAt <=',$end_date); 
        }
        if($customer_id!=''){

            $this->db->where('customer_id',$customer_id); 
        }
        $query = $this->db->get();

        return $query->result();
    }

    /**
     * delete braintree payment info of user
     * @param associative array $data     
     * return integer
     */
    public function delete_customer_braintree_data($customer_id) {
       $this->db->where('customer_id',$customer_id);
       return $this->db->delete('braintree_transaction');
    }





    /**
     * insert subscription details
     * @param associative array $data     
     * return integer
     */
    public function save_subscription($data) {
        $this->db->insert('payment_subscription', $data);
        return $this->db->insert_id();
    }

    /**
     * update payment details
     * @param int id
     * @param associative array $data     
     * return object
     */
    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update($this->table_name, $data);
    }



    /**
     * update payment method details
     * @param int id
     * @param associative array $data     
     * return object
     */
    public function update_payment_method($id, $data) {
        $this->db->where('user_id', $id);
        return $this->db->update('payment_methods', $data);
    }



    public function delete_payment_method($user_id) {
        $this->db->where('user_id',$user_id);
        return $this->db->delete('payment_methods');
    }

    /** 
    * get payment history by user_id
    * $user_id     
    * return row
     */
   
   public function get_payment_info($user_id,$start_date='',$end_date='') {
            if($start_date!=''&& $end_date!=''){
               $this->db->where('created_date >=',$start_date); 
               $this->db->where('created_date <=',$end_date); 
            }
            $this->db->where('user_id', $user_id);
            $this->db->where('transaction_type','1');
            $query = $this->db->get($this->table_name);
            return $query->row();
    }

    /** 
    * get payment installment by payment_id
    * $payment_id     
    * return array
     */
   
   public function get_installment_info($user_id,$start_date='',$end_date='') {
            if($start_date!=''&& $end_date!=''){
                $this->db->where('created_date >=',$start_date); 
               $this->db->where('created_date <=',$end_date);
            }
            $this->db->where('user_id', $user_id);
            $this->db->where('transaction_type','0');
            $query = $this->db->get($this->table_name);
            return $query->result();
    }

    /** 
    * get payment history by user id
    * $user_id     
    * return boolean
     */
   
   public function get_last_installment($user_id) {
           /* $query = $this->db->query("Select * from payment_subscription where payment_id='$payment_id' order by id desc");*/
            $query = $this->db->query("Select * from transaction_history where user_id='$user_id' and transaction_type='0' order by id desc");
            return $query->row();
    }


    public function get_last_installment_for_invoice($payment_method,$user_id) {
           /* $query = $this->db->query("Select * from payment_subscription where payment_id='$payment_id' order by id desc");*/
            $query = $this->db->query("Select * from transaction_history where user_id='$user_id' and payment_method='$payment_method' order by id desc");
            return $query->row();
    }


    /**
     * insert payment methos of braintree
     * @param associative array $data     
     * return integer
     */
    public function save_payment_method($data) {
        $this->db->insert('payment_methods', $data);
        return $this->db->insert_id();
    }

    /**
     * gets a single record of payment method of user
     * @param int $id     
     * return object
     */
    public function get_default_payment_method($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->where('is_default', 1);
        $query = $this->db->get('payment_methods');
        return $query->row();

    }


        /**
     * gets a single record of initial transaction of user
     * @param int $id     
     * return object
     */
    public function get_initial_transaction($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->where('transaction_type', 1);
        $query = $this->db->get('transaction_history');
        return $query->row();

    }

    public function get_country_by_country_code($c_code){
       $this->db->where('country_code', $c_code);
       $query = $this->db->get('taxamo_countries');
       return $query->row(); 
    }


     public function transaction_entry($user_id) {


                $empty = '';
                $user_details = $this->user_model->get_user($user_id);        
                
                $payement_method_details = $this->payment_model->get_default_payment_method($user_id);
                $payment_method = $payement_method_details->id;
                $vat_rate = $user_details->vat;
                $subscription_amount = SUBSCRIPTION_AMT;
                $total_amount = ((100+$vat_rate)/100)*$subscription_amount; // including vat

                $customer_id = $user_details->customer_id;

                $transaction_result = Braintree_Transaction::sale(
                    array(
                        'customerId' => $customer_id,
                        'amount' => $total_amount,
                        'options' => array(
                        'submitForSettlement' => true
                        ),
                    )
                );


                if ($transaction_result->success) {

                    //prepare the data to store in the database
                    $pay_instrument = $transaction_result->transaction->paymentInstrumentType;
                    $pay_details = $pay_instrument == 'credit_card' ? $transaction_result->transaction->creditCard : $transaction_result->transaction->paypal;
                    $payment_method_braintree = $transaction_result->transaction->paymentInstrumentType;

             
                    $payment_data = array();
                    $payment_data['user_id'] = $user_id;
                    $payment_data['user_type'] = $empty;
                    $payment_data['billing_address'] = $empty;
                    $payment_data['payment_method'] = $payment_method;
                    
                    //$payment_data['payment_date'] = time();
                    $payment_data['discount'] = 0;
                    $payment_data['country_of_origin'] = $empty;
                    $payment_data['vat_number'] = $empty;
                    $payment_data['vat_rate'] = $vat_rate;
                    $payment_data['subscription_amount'] = $total_amount;
                    $payment_data['sms_verification'] = $empty;
                    $payment_data['manual_verification'] = $empty;
                    $payment_data['coupon'] = $empty;
                    $payment_data['created_date'] = time();

                    $payment_data['payment_status'] = 1;
                    $payment_data['invoice_number'] = $transaction_result->transaction->id;
                    $payment_data['settlement_status'] = $transaction_result->transaction->status;
                    $payment_data['transaction_number'] = $transaction_result->transaction->id;

                    $payment_data['transaction_token'] = $pay_details['token'];
                    $payment_data['info'] = json_encode($pay_details);

                    $payment_data['transaction_type'] = 0;

                    //var_dump($payment_data);die;

                    //save the transaction
                    $transaction_id = $this->payment_model->save($payment_data);
                     $response = array(
                        'success'=> true,
                        'transaction_id' => $transaction_result->transaction->id,
                        'customer_id' => $customer_id,
                        'status' => $transaction_result->transaction->status,
                        'amount' => $transaction_result->transaction->amount,
                        'info' => $pay_details,
                        'paymentInstrumentType'=> $pay_instrument,
                        'internal_transaction_id' => $transaction_id,
                    );
                    return $response;
                    //var_dump($response);
                    //send email
                } else if ($transaction_result->transaction) {

                    $response = array(
                        'error'=> true,
                        'transaction_code' => $transaction_result->transaction->processorResponseCode,
                        'customer_id' => $customer_id,
                        'msg' => $transaction_result->transaction->processorResponseText,
                    );
                     return $response;
                    /*print_r("Error processing transaction:");
                    print_r("\n  code: " . $transaction_result->transaction->processorResponseCode);
                    print_r("\n  text: " . $transaction_result->transaction->processorResponseText);*/
                } else {

                     $response = array(
                        'error'=> true,
                        'msg' => $transaction_result->errors->deepAll(),
                        'customer_id' => $customer_id,
                    );
                     return $response;
                    /*print_r("Validation errors: \n");
                    print_r($transaction_result->errors->deepAll());*/
                }



    }

    function generate_invoice_pdf($transaction_id){

                //ob_clean();
        $this->load->library('tcpdf/tcpdf');
        //$transaction_id = '66h2qt';
        if($transaction_id!=''){
              //getting transaction detail with id
             $transaction = Braintree_Transaction::find($transaction_id);
             if(!empty($transaction)){
                $data['transaction'] = $transaction;

                //$pdf_view = 'hello test';
                $pdf_view = $this->load->view('invoice_pdf_view',$data,true);

                 $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);               
                // set document information
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetAuthor('Nicola Asuni');
                $pdf->SetTitle('TCPDF Example 006');
                $pdf->SetSubject('TCPDF Tutorial');
                $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
                // remove default header/footer
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                $pdf->SetHeaderMargin(0);
                $pdf->SetTopMargin(0);
                // set default header data
                //$pdf->SetHeaderData('', '','', '');
                //echo PDF_HEADER_LOGO; 
                //$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 006', PDF_HEADER_STRING);

                $pdf->setFooterData('lorem ipsum');

                // set header and footer fonts
                $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
                $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

                // set default monospaced font
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

                // set margins
                $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
                $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
                $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

                // set auto page breaks
                $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

                // set image scale factor
                //$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);


                // ---------------------------------------------------------

                // set font
                $pdf->SetFont('dejavusans', '', 10);

                // add a page
                $pdf->AddPage();

                // writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='')
                // writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=0, $reseth=true, $align='', $autopadding=true)

                // create some HTML content
                $html = $pdf_view;

                // output the HTML content
                $pdf->writeHTML($html, true, false, true, false, '');




                // $pdf->WriteHTML(5, 'dummy dataaafasdfsfsd');
                //$pdf->writeHTML(utf8_encode($pdf_view), true, 0, true, 0);
                $pdf_filename = 'physio_invoice'.$transaction_id.'.pdf';
                //file_put_contents(base_url().'invoices/test.php','');
                //$file_path = '/invoices/';
                $pdf->Output(INVOICEPATH.$pdf_filename,'F');

                //return true;

                /*$this->template->write_view('content', 'invoice_pdf_view', $data);
                $this->template->render();*/
             }else{
                return false;
             }

        }else{
           return false;
        }

    }



}