<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Payment extends MY_Controller {

    function __construct() {

        parent::__construct();
        $this->template->set_template('frontend');
        $this->load->model('payment/payment_model');
        $this->load->model('public_model');
        $this->load->model( 'user/user_model');
        $this->lang->load('flashdata');

        //$this->load->helper('upload_helper');
        include("vendor/taxamo/taxamo-php/lib/Taxamo.php");
        include 'vendor/braintree/braintree_php/lib/Braintree.php';

       //braintree settings
        Braintree_Configuration::environment('sandbox');
        Braintree_Configuration::merchantId(BRAINTREE_MERCHANT_ID);
        Braintree_Configuration::publicKey(BRAINTREE_PUBLIC_KEY);
        Braintree_Configuration::privateKey(BRAINTREE_PRIVATE_KEY);


        // loading js
        load_js("jquery_validate");
      
    }


    public function test_pdf(){
       $response_pdf = $this->payment_model->generate_invoice_pdf('3mnmmn'); 
    }

    /**
     *
     * This function displays the login form
     *
     */
    public function index(){


        $user_info = get_session_user_info();
        $user_det = $this->user_model->get_user($user_info['id']);
        $subscription_status = false;
        $ustidno = '';
        if(is_practice($user_info['id'])){

                $ustidno = !empty($user_det->ust_id) ? $user_det->ust_id : '';
                if(check_payment_info_for_trial_period_practice($user_info['id']))
                {
                    $subscription_status = true;

                }else{
                    $subscription_status = false;
                }
           
        }else{
         $subscription_status = check_payment_info($user_info['id']);   
        }
        //$subscription_status = check_payment_info($user_info['id']);
        $redirect_url = '';

        //if(is_therapist($user_info['id']) || is_therapist_assist($user_info['id'])){
        //if($user_det->original_role_id=='3' || $user_det->original_role_id=='5'){
        if($user_det->original_role_id=='3'){ //only therapist cannot pay
            $this->session->set_flashdata("access_denied", lang("fd.access_denied"));
            if(is_therapist($user_info['id'])){
                redirect(base_url().'content/practice/home');

            }else{
                redirect(base_url().'content/patient/home');

            }                           
        }

         $data = array();
         $data['is_practice'] = false; 
        if(is_practice($user_info['id'])){
            $redirect_url = base_url().'practice/dashboard';
            $data['is_practice'] = true;
        }else if(is_self_service($user_info['id']) || $user_det->original_role_id=='5'){
            $redirect_url = base_url().'patient/dashboard';
        }

       
        $data['redirect_url'] = $redirect_url;
        $data['ustidno'] = $ustidno;

        if($subscription_status) {
            $data['paid'] = TRUE;
        } else {
            $data['paid'] = FALSE;
            load_js('payment');
            //$data['country_list'] = $this->payment_model->getCountries();
            //var_dump($data['country_list']);die;
            
            $data['user_details'] = $this->user_model->get_user($user_info['id']);
            $data['origin'] = $this->payment_model->get_origin_country();

            $clientToken = Braintree_ClientToken::generate();

            $data['client_token'] = $clientToken;

            //var_dump($data['origin']);die;
            //var_dump($data['user_details']);die;
        }
        
        $this->template->write_view('content', 'payment_pay_view',$data);
        $this->template->render();
     

	}


       public function get_taxinfo(){
        
        //$vatno = 'DE270819281';//$this->input->post('vat_no');DE270819281 FR90349166561
        $country_code = $this->input->post('cc');
        $vatno = $this->input->post('vatno');
        if($vatno) {
             $tax_info = $this->payment_model->getTaxInfo($vatno); //with vatno
        } else {
            $tax_info = $this->payment_model->getTaxInfo(NULL,$country_code); //without vatno
        }
        
        if(isset($tax_info->errors)){
            $tax_info->countryerror = true;
            $tax_info->error = $tax_info->errors[0];
            echo json_encode($tax_info);
        } else {
            echo json_encode($tax_info);
        }

        //var_dump($tax_info->transaction->tax_country_code);die;
        //var_dump($tax_info->transaction->tax_entity_name);die;
        //var_dump($tax_info->transaction->buyer_tax_number_valid);die;
        //var_dump($tax_info->transaction->transaction_lines[0]->tax_rate);die;
        //var_dump($tax_info->transaction->transaction_lines[0]->tax_amount);die;


        }

            /**
     * Check whether vat number exists or not.
     */
   /* public function verify_vatnumber(){

        //$tax_info = $this->payment_model->verify_vatnumber('DE','DE270819281');
        
        $vatno = $this->input->post('vatno');
        $country_code = '';

        $tax_info = $this->payment_model->verify_vatnumber($country_code,$vatno);
       
       //var_dump($tax_info);die;
        object(ValidateTaxNumberOut)#27 (4) {
        ["tax_deducted"]=>
        bool(true)
        ["buyer_tax_number"]=>
        string(11) "GB938856562"
        ["buyer_tax_number_valid"]=>
        bool(true)
        ["billing_country_code"]=>
        string(2) "GB"
        }
        //$tax_info->buyer_tax_number_valid;
        
        if ($tax_info->buyer_tax_number_valid ) {
            echo "1";
        }
        else {
            echo "0";
        }
    }*/

public function process_checkout_info($post) {

            $session_data = $this->session->userdata('fr_logged_in');
            $user_id = $session_data['id'];
            $user_details = $this->user_model->get_user($user_id);

            $billing_type = isset($post['billing']) ? $post['billing'] : ''; // old or new
            $data = JSON_decode($post['data']); //object
            $user_type = $data->user_type; // private person or practice

           
            $new_billing_conact = new stdClass();

            if($billing_type == 1) { //new address

                $first_name = $post['first_name'];
                $last_name = $post['last_name'];
                $street_address = $post['street']. ' '.$post['street_num'];
                $city = $post['city'];
                $country_code = $post['country'];
                $zip = $post['zip'];
                $company = $post['company'];
                $email = $user_details->email;
                
                // prepare object to store in db
                $new_billing_conact->first_name = $_POST['first_name'];
                $new_billing_conact->last_name = $_POST['last_name'];
                $new_billing_conact->street = $post['street']. ' '.$post['street_num'];
                $new_billing_conact->city = $_POST['city'];
                $new_billing_conact->zip = $_POST['zip'];
                $new_billing_conact->country = $_POST['country'];
                $new_billing_conact->company = $_POST['company'];

            } else {
                $first_name = $user_details->first_name;
                $last_name = $user_details->last_name;
                $street_address = $user_details->street_and_number . ' '. $user_details->street_num;
                $city = $user_details->city;
                $country_code = $user_details->country_code;
                $zip = $user_details->zipcode;
                $email = $user_details->email;
                $company = $user_details->company_name;
            }

            $billing_info = array('type'=>$billing_type,'new_contact'=>$new_billing_conact);
            $billing_info = serialize($billing_info);
           

            $vat_rate = $data->vat_rate;
            $vat_no = $data->vat_no;
            $payment_method = $data->payment_method;
            $discount = $data->discount;
            $total = $data->total;
            $owner = $data->owner; //credit card name
            $manual_verification = $data->manual_verification ? $data->manual_verification : '';
            $sms_verification = $data->sms_verification ? $data->sms_verification : '';
            //var_dump($sms_verification);die;
            
           
            $initial_subscription_amt = $data->total; //initially

               
            //prepare the data to store in the database
            $payment_data = array();
            $payment_data['user_id'] = $user_id;
            $payment_data['user_type'] = $user_type;
            $payment_data['billing_address'] = $billing_info;
            $payment_data['payment_method'] = $payment_method;
            
            //$payment_data['payment_date'] = time();
            $payment_data['discount'] = $discount;
            $payment_data['country_of_origin'] = $data->origin;
            $payment_data['vat_number'] = $vat_no;
            $payment_data['vat_rate'] = $vat_rate;
            $payment_data['subscription_amount'] = $initial_subscription_amt;
            $payment_data['sms_verification'] = serialize($sms_verification);
            $payment_data['manual_verification'] = $manual_verification;
            $payment_data['coupon'] = $data->coupon;
            $payment_data['created_date'] = time();

            $payment_data['user_info'] = array(
                'user_id' =>$user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'street_address' => $street_address,
                'city' => $city, 
                'country_code' => $country_code,
                'zip' => $zip, 
                'company' => $company,
                'email' => $email,
                );

            $payment_data['misc'] = array(
                'owner' => $owner,
                'physio_payment_method' => $payment_method,
                'nonce' => $data->nonce,
                );


            return $payment_data;
}

	public function checkout() {
        if ($_POST) {
            //var_dump($_POST);die;

            $payment_data = $this->process_checkout_info($_POST);
            //$nonce = $payment_data['misc']['nonce'];

            //var_dump($payment_data['user_info']['first_name']);die;

            /* credit card payment details*/
            $postal_code = $payment_data['user_info']['zip']; // this is required .. at least one of the address field is required
            $owner = $this->input->post('owner');
            $cc_number = $this->input->post('number');
            $year = $this->input->post('year');
            $month = $this->input->post('month');
            $cvv = $this->input->post('cvv');
           
            // call brain tree api to create a new customer
            $customer_result = Braintree_Customer::create(array(
                "firstName" => $payment_data['user_info']['first_name'],
                "lastName" => $payment_data['user_info']['last_name'],
                "email" => $payment_data['user_info']['email'],
                "company"=> $payment_data['user_info']['company'],
                "creditCard" => array(
                    "number" => $cc_number,
                    "expirationMonth" => $month,
                    "expirationYear" => $year,
                    "cvv" => $cvv,
                    "billingAddress" => array(
                        "postalCode" => $postal_code,
                        'firstName' => $payment_data['user_info']['first_name'],
                        'lastName' => $payment_data['user_info']['last_name'],
                        'company' => $payment_data['user_info']['company'],
                        'streetAddress' => $payment_data['user_info']['street_address'],
                        'locality' => $payment_data['user_info']['city'],
                        'countryCodeAlpha2' => $payment_data['user_info']['country_code'],   
                    ),
                    'options' => array(
                        'verifyCard' => true
                    )
                )
            ));

            //var_dump($customer_result);die;
            if ($customer_result->success) {
                
                $customer_id = $customer_result->customer->id;

                // save baintree vault customer id in user table
                $this->user_model->update($payment_data['user_id'], array('customer_id'=>$customer_id,'braintree_subscription_status'=> 1));

                //create transaction after creating customer
                //unset($payment_data['user_info']);
                $this->make_transaction($customer_id,$payment_data['subscription_amount'],$payment_data);

            } else {
                $verification = $customer_result->creditCardVerification;
                $msg = $customer_result->message;
                
                $response = array(
                    'error'=> true,
                    'msg'=> $msg,
                    'verification_status' => $verification ? $verification->status : '',
                    'verification_res_code' => $verification ? $verification->processorResponseCode : '',
                    'verification_res_text' => $verification ? $verification->processorResponseText : '',
                    );
                echo json_encode($response);
                /*print_r("Invalid credit card: \n");

                $verification = $customer_result->creditCardVerification;
                echo $verification->status;
                // "processor_declined"
                echo $verification->processorResponseCode;
                // "2000"
                echo $verification->processorResponseText;
                // "Do Not Honor"*/

            }


        }

	}


    public function do_paypal_payment() {
            $response = $_POST;
            $payment_data = $this->process_checkout_info($response);
            $nonce_from_client = $response['nonce'];
          
           
            try{
                $result = Braintree_Transaction::sale(array(
                        'amount' => INTIAL_SUBSCRIPTION_AMT,
                        'merchantAccountId' => BRAINTREE_MERCHANT_ACCOUNT_ID,
                        'paymentMethodNonce' => $nonce_from_client,

                        'customer' => array(
                            /*'id' => "23530",*/
                            'firstName' => $payment_data['user_info']['first_name'],
                            'lastName' => $payment_data['user_info']['last_name'],
                            'email' => $payment_data['user_info']['email'],
                            'company'=> $payment_data['user_info']['company'],
                        ),
                        /*'billing' => array(
                            'firstName' => $first_name,
                            'lastName' => $last_name,
                            'streetAddress' => $street_address,
                            'locality' => $city,
                            'postalCode' => $zip,
                            'company' => $company,
                            'countryCodeAlpha2' => $country_code,
                        ),*/
                        'options' => array(
                            'submitForSettlement' => true,
                            'storeInVaultOnSuccess' => true,
                        )
                    ));
                    
                     # true
                    //$result->transaction->status
                    # e.g. 'submitted_for_settlement'
                    //$result->transaction->type
                    # e.g. 'credit'
                    if($result->success) {
                        
                        $customer = $result->transaction->customer;



                        $customer_id = $customer['id'];

                        // save baintree vault customer id in user table
                        $this->user_model->update($payment_data['user_id'], array('customer_id'=>$customer_id, 'braintree_subscription_status'=> 1));

                        //create transaction after creating customer
                        //unset($payment_data['user_info']);
                        $this->make_transaction($customer_id,$payment_data['subscription_amount'],$payment_data);
                        
                    } else {

                       $response = array(
                                'success'=> false,
                                'msg' => $result->message,
                            );
                            echo json_encode($response);
                    }

            } catch(Braintree_Exception_Authorization $ex) {
                 var_dump($ex->errors->deepAll()); //not handled yet remaining...
            }


    }

    public function make_transaction($braintree_customer_id, $total_amount,$data = NULL ,$type = 'initial', $change = FALSE){

                $customer_id = $braintree_customer_id;

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
                    //print_r("success!: " . $transaction_result->transaction->id);


                    $pay_instrument = $transaction_result->transaction->paymentInstrumentType;
                    //$credit_card_details = $transaction_result->transaction->creditCard; // array
                    $pay_details = $pay_instrument == 'credit_card' ? $transaction_result->transaction->creditCard : $transaction_result->transaction->paypal;

                     //var_dump($pay_details);die;

                    $response = array(
                        'success'=> true,
                        'transaction_id' => $transaction_result->transaction->id,
                        'customer_id' => $customer_id,
                        'status' => $transaction_result->transaction->status,
                        'amount' => $transaction_result->transaction->amount,
                        'info' => $pay_details,
                        'paymentInstrumentType'=> $pay_instrument,
                    );


                    //insert the prepared data in the db
                    if(!is_null($data)) {
                         $session_data = $this->session->userdata('fr_logged_in');
                        $user_id = $session_data['id'];

                           //save the payment method of customer
                        //$this->payment_model->update_payment_method($user_id,array('is_default'=>0));

                        if($change) {
                            $this->payment_model->delete_payment_method($user_id);
                            $uid = $session_data['id'];
                            change_payment_method_mail_send($uid); // send email to the user when payment details are changed
                 
                        } 

                        $payment_method_braintree = $transaction_result->transaction->paymentInstrumentType;
                        $payment_method_id = $this->payment_model->save_payment_method(array(
                            'user_id'=>$user_id,'name'=>$payment_method_braintree,'is_default'=>1,
                            'owner'=> $data['misc']['owner'],
                            'physio_payment_method'=> $data['misc']['physio_payment_method'],
                            'token' => $pay_details['token'],
                            ));
                        

                        //add payment method in braintree

                        //$res = $this->add_payment_method($data['misc']['physio_payment_method'], $customer_id, $data['misc']['nonce']);
                        //var_dump($res);

                        //update the default payment method
                       // $res = $this->update_payment_method($pay_details['token']);
                       

                        //transaction history table
                        $data['payment_status'] = 1;
                        $data['invoice_number'] = $transaction_result->transaction->id;
                        $data['settlement_status'] = $transaction_result->transaction->status;
                        $data['transaction_number'] = $transaction_result->transaction->id;
                        
                        $data['transaction_token'] = $pay_details['token'];
                        $data['info'] = json_encode($pay_details);
                        
                        $data['payment_method'] = $payment_method_id;
                        $data['transaction_type'] = ($type == 'new') ? 0 : 1; // new payment methods are also given 0 flag like monthly

                        unset($data['user_info']);
                        unset($data['misc']);
                        //save the transaction
                        $this->payment_model->save($data);


                        //insert used coupon
                        if(!empty($data['coupon'])) {
                            $this->load->model( 'admin/coupon_model');
                            $coupon_details = $this->coupon_model->get_coupon($data['coupon']);
                            $data_coupon = array();
                            $data_coupon['user_id'] = $data['user_id'];
                            $data_coupon['coupon_id'] = $coupon_details->id;
                            $this->coupon_model->save($data_coupon);
                        }

                        //update the role of therapist assisted to self serice whey he pays
                        if(is_therapist_assist($user_id)){
                            $usr_data = array('role_id'=>'4');
                            $this->user_model->update($user_id,$usr_data);
                        }

                        // save vat rate id in user table
                        $this->user_model->update($user_id , array('vat'=>$data['vat_rate']));

                    } 

                    //send payment email
                        $user_details = $this->user_model->get_user($user_id);
                        $email = $user_details->email;
                        $first_name = $user_details->first_name;
                        $last_name = $user_details->last_name;
                        $username = $first_name . " " . $last_name;
                        $gender = $user_details->gender;
                        
                        //send payment invoice  
                        $response_pdf = $this->payment_model->generate_invoice_pdf($data['transaction_number']);

                        //if($response_pdf) {
                            $attchment = INVOICEPATH.'physio_invoice'.$data['transaction_number'].'.pdf';
                            if(file_exists($attchment)) {
                                sent_payment_invoice($email,$username,$gender,$transaction_result->transaction->id,$attchment);
                            }  
                        //} 
                        echo json_encode($response);

                } else if ($transaction_result->transaction) {

                    $response = array(
                        'error'=> true,
                        'transaction_code' => $transaction_result->transaction->processorResponseCode,
                        'customer_id' => $customer_id,
                        'msg' => $transaction_result->transaction->processorResponseText,
                    );
                    echo json_encode($response);
                    /*print_r("Error processing transaction:");
                    print_r("\n  code: " . $transaction_result->transaction->processorResponseCode);
                    print_r("\n  text: " . $transaction_result->transaction->processorResponseText);*/
                } else {

                     $response = array(
                        'error'=> true,
                        'msg' => $transaction_result->errors->deepAll(),
                        'customer_id' => $customer_id,
                    );
                    echo json_encode($response);
                    /*print_r("Validation errors: \n");
                    print_r($transaction_result->errors->deepAll());*/
                }
    }



   public function verify_voucher(){

     $this->load->model( 'admin/coupon_model');

    $coupon = $this->input->post('vc');
   
    $voucher_info = $this->coupon_model->is_coupon_valid($coupon);
    echo json_encode($voucher_info);

    }

    public function create_sms_token() {
        $country_code=$this->input->post('cc');
        $number = $this->input->post('handynummer');
        $taxamo = new Taxamo(new APIClient(TAXAMO_PRIVATE_KEY, 'https://api.taxamo.com'));
        $receiver_info = array('country_code'=>$country_code,'recipient'=>$number,'public_token'=>TAXAMO_PUBLIC_KEY);
        //$receiver_info = array('country_code'=>'NP','recipient'=>'9841652848','public_token'=>TAXAMO_PUBLIC_KEY); 
        
        try {
            $result = $taxamo->createSMSToken($receiver_info);
            echo json_encode($result); //returns boolean value
        }catch(TaxamoValidationException $ex){
            $result = $ex->errors[0];
            echo json_encode($result); //error msg
        } catch(TaxamoAPIException $ex) {
            $response = $ex->response; 
            $error_response = json_decode($response);
            $error = $error_response->errors;
            //var_dump($error[0]);
            echo json_encode($error);
             //echo json_encode("sms couldn't be sent"); //error msg
        }

        
        
    }

    public function verify_sms_token() {
        $token = $this->input->post('token');
        $taxamo = new Taxamo(new APIClient(TAXAMO_PRIVATE_KEY, 'https://api.taxamo.com'));
        $result = $taxamo->verifySMSToken($token);
        echo json_encode($result); //return country code on source else null
        
    }

    public function get_calling_code($cc) {
        if(empty($cc)) {
            $response = array("nocountryinput"=>TRUE,"nocountrymsg"=>"No country added in profile");
            echo json_encode($response);
        } else {
            $country_detail = $this->user_model->_get_country($cc);
            echo json_encode($country_detail->calling_code);
        }
    }

    /** 
    * generate and save invoice pdf
    * @param int $transaction_id     
    * 
     */

    /*public function generate_invoice_pdf($transaction_id){
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
                $pdf->SetHeaderMargin(0);
                $pdf->SetTopMargin(0);
                // set default header data
                $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH,HEADER_TEXT, HEADER_STRING);
                //echo PDF_HEADER_LOGO; 
                //$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 006', PDF_HEADER_STRING);

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

             }else{
                redirect_to_url(); 
             }

        }else{
            redirect_to_url();
        }
    }*/

        public function get_braintree_transaction_list(){

        $sess_data = $this->session->userdata('fr_logged_in');
        $user_id = $sess_data['id'];
        $result = '';
        //$payment_info = $this->payment_model->get_payment_info($user_id);
        $user_info = $this->user_model->get_user($user_id);

        if($user_info->customer_id){

            $customer_id = $user_info->customer_id;
        }else{
            $customer_id='';
        }


        if($customer_id!=''):
         $transaction_id_collection = Braintree_Transaction::search(array(
            Braintree_TransactionSearch::customerId()->is($customer_id),
        ));
        //deleting customer transaction data
        $this->payment_model->delete_customer_braintree_data($customer_id);
        //end of deleting customer transaction data 
        if(!empty($transaction_id_collection)):
        foreach ($transaction_id_collection->_ids as $key => $idval) :
            
            $transaction = Braintree_Transaction::find($idval);

        $date_arr = get_object_vars($transaction->_attributes['createdAt']);

        if($transaction->_attributes['paymentInstrumentType']=='credit_card'){
            $info = json_encode($transaction->_attributes['creditCard']);
        }else{
            $info = json_encode($transaction->_attributes['paypal']);
        }
        
        $data = array( 'id'=>$transaction->_attributes['id'],
                       'amount'=>$transaction->_attributes['amount'],
                       'status'=>$transaction->_attributes['status'],
                       'createdAt'=>strtotime(date('Y-m-d',strtotime($date_arr['date']))),
                       'customer_id'=>$transaction->_attributes['customer']['id'],
                       'info'=>$info,
                       'paymentInstrumentType'=>$transaction->_attributes['paymentInstrumentType'],
                       'payment_token_id'=>$transaction->_attributes['creditCard']['token']
            );

       $this->payment_model->save_braintree_transaction($data);
                                
        endforeach;
        $data['result'] = $this->payment_model->get_braintree_transaction($customer_id);
        endif;
        
        else:
            $data['result'] = '';
        endif;

        /*echo "<pre>";
        print_r($result);
        die();*/

        $this->load->view('braintree_transcation_list_view',$data);
    }

     public function load_paypal_form(){
         $data = array();
        $clientToken = Braintree_ClientToken::generate();
        $data['client_token'] = $clientToken;
        $html = $this->load->view('paypal_form',$data,true);
        echo $html;
        //echo json_encode($clientToken);
        /*
        $data['client_token'] = $clientToken;
        if(!empty($clientToken)) {
            $this->load->view('paypal_form',$data);
        }*/

    }


    public function load_steps($step_no) {
        $data = array();
        $data['ustidno'] = '1234';
        $html = $this->load->view('step-'.$step_no,$data);
        echo $html;
    }

    public function change() {

       /*  load_js('payment');
        $data = array();
        $clientToken = Braintree_ClientToken::generate();
        $user_info = get_session_user_info();
        $data['user_details'] = $this->user_model->get_user($user_info['id']);
        $data['client_token'] = $clientToken;
        $this->template->write_view('content', 'change_payment_method',$data);
        $this->template->render();*/


        $redirect_url = '';
        $ustidno = '';
        $data = array();
        $user_info = get_session_user_info();
        $user_det = $this->user_model->get_user($user_info['id']);

        $data['is_practice'] = false; 

        $customer_id = $user_det->customer_id;

        //if(is_therapist($user_info['id']) || is_therapist_assist($user_info['id'])){
        //if($user_det->original_role_id=='3' || $user_det->original_role_id=='5'){
        if($user_det->original_role_id=='3' || !$customer_id){ //only therapist cannot pay
            $this->session->set_flashdata("access_denied", lang("fd.access_denied"));
            if(is_therapist($user_info['id'])){
                redirect(base_url().'content/practice/home');

            }else{
                redirect(base_url().'content/patient/home');

            }                           
        }


        if(is_practice($user_info['id'])){
            $redirect_url = base_url().'practice/dashboard';
            $data['is_practice'] = true;
            $ustidno = !empty($user_det->ust_id) ? $user_det->ust_id : '';
        } else if(is_self_service($user_info['id']) || $user_det->original_role_id=='5'){
            $redirect_url = base_url().'patient/dashboard';
        }

        $data['redirect_url'] = $redirect_url;
        $data['ustidno'] = $ustidno;

        load_js('payment');

        $data['user_details'] = $this->user_model->get_user($user_info['id']);
        $data['origin'] = $this->payment_model->get_origin_country();

        $clientToken = Braintree_ClientToken::generate();

        $data['client_token'] = $clientToken;
        
        $this->template->write_view('content', 'change_method',$data);
        $this->template->render();
    }

    /*public function add_payment_method() {

        $payment_data = $this->process_checkout_info($_POST);
        //var_dump($payment_data);die;
        $data = JSON_decode($_POST['data']); //object
        //var_dump($data);die;
        $payment_method = $data->payment_method;
        $customer_id = $data->customerId;
        $payment_nonce = $data->nonce;
        $total = $data->total;

        if($payment_method == "paypal") {
            $result = Braintree_PaymentMethod::create(array(
                'customerId' => $customer_id,
                'paymentMethodNonce' => $payment_nonce,
                'options' => array(
                    'makeDefault' => true
                )
            ));
        } else {

            //for credit card
            $result = Braintree_PaymentMethod::create(array(
                'customerId' => $customer_id,
                'paymentMethodNonce' => $payment_nonce,
                'options' => array(
                    //'failOnDuplicatePaymentMethod' => true,
                    'makeDefault' => true,
                    'verifyCard' => true,
                    'verificationMerchantAccountId' => BRAINTREE_MERCHANT_ACCOUNT_ID,
                )
            ));
        }
        //var_dump($result);die;
         
            if($result->success) {
                $response = array(
                    'success'=> true,
                    'customer_id' => $customer_id,
                    'token' => $result->paymentMethod->token,
                    'msg' => '',
                );

                //$result->paymentMethod->email // for paypal emailaddress
                //$result->paymentMethod->customerId
                //$result->paymentMethod->cardType
                
                 $this->make_transaction($customer_id,$total,$payment_data,'new');

            } else {
                   $response = array(
                    'success'=> false,
                    'customer_id' => $customer_id,
                    'msg' => $result->message,
                );
                echo json_encode($response);
            }   


    }*/


    public function add() {

       $payment_data = $this->process_checkout_info($_POST);
        //var_dump($payment_data);die;
        $data = JSON_decode($_POST['data']); //object
        //var_dump($data);die;
        $payment_method = $data->payment_method;
        $customer_id = $data->customerId;
        $payment_nonce = $data->nonce;
        $total = $data->total;

        if($payment_method == "paypal") {  
            $result = Braintree_PaymentMethod::create(array(
                'customerId' => $customer_id,
                'paymentMethodNonce' => $payment_nonce,
                'options' => array(
                    'makeDefault' => true
                )
            ));
        } else {

            //for credit card
            $result = Braintree_PaymentMethod::create(array(
                'customerId' => $customer_id,
                'paymentMethodNonce' => $payment_nonce,
                'options' => array(
                    //'failOnDuplicatePaymentMethod' => true,
                    'makeDefault' => true,
                    'verifyCard' => true,
                    'verificationMerchantAccountId' => BRAINTREE_MERCHANT_ACCOUNT_ID,
                )
            ));
        }
        //var_dump($result);die;
         
            if($result->success) {
                $response = array(
                    'success'=> true,
                    'customer_id' => $customer_id,
                    'token' => $result->paymentMethod->token,
                    'msg' => '',
                );

                //$result->paymentMethod->email // for paypal emailaddress
                //$result->paymentMethod->customerId
                //$result->paymentMethod->cardType
                 $this->make_transaction($customer_id,$total,$payment_data,'new', TRUE);

            } else {
                   $response = array(
                    'error'=> true,
                    'customer_id' => $customer_id,
                    'msg' => $result->message,
                );
                   //var_dump($result);
                echo json_encode($response);
            }   


    }

//update the default payment method
    public function update_payment_method($token) {
        $updateResult = Braintree_PaymentMethod::update(
            $token,
            array(  
                'options' => array(
                'makeDefault' => true
            )
        ));
       
        return $updateResult;
    }



    public function look_customer($customer_id) {
        $customer_id = '32998154';
        $customer = Braintree_Customer::find($customer_id);
        var_dump($customer->paypalAccounts);die;
        //$customer->creditCards
    }


    /*Find expiring credit cards*/
    function get_expired_credit_cards() {
/*get the details of credit card*/
$paymentMethod = Braintree_PaymentMethod::find('b6q4xw');
         var_dump($paymentMethod);die;

        $process_date = date('Y-01-01');
        $start_date = strtotime("-5 year", strtotime($process_date));
        //echo date("Y-m-d",$process_date);die;
        $current_date = date('Y-m-d');
        //echo $current_date;
        $current_unix_time = time();
        $till_date = strtotime("+7 day", strtotime($current_date));
        //echo date("Y-m-d",$till_date);die;

              $cards = Braintree_CreditCard::expiringBetween(
              $start_date,$till_date);
              var_dump($cards);
    }


}

