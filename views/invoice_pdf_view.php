<?php 

        $sess_user_info = get_session_user_info();
        $user_det = $this->user_model->get_user($sess_user_info['id']);

        
        $attributes = $transaction->_attributes;
        
        
        $payment_method = $this->payment_model->get_default_payment_method($sess_user_info['id']);
        $last_installment = $this->payment_model->get_last_installment_for_invoice($payment_method->id,$sess_user_info['id']);
        //echo "<pre>";
        $billing_address = unserialize($last_installment->billing_address);

        //$country_name = $billing_address['new_contact']->country;
        $country_name = $attributes['billing']['countryCodeAlpha2'];
        $vat_number='';
        if($country_name=='DE'){
            $tax_liablity = "Steuerschuldnerschaft des Leistungsempfängers";
            if($last_installment->vat_number!='N/A'){
                $vat_number = $last_installment->vat_number;
            }
        }else{
            $tax_liablity = '';

            if($last_installment->vat_rate!=0){
                if($last_installment->vat_number!='N/A'){
                $vat_number = $last_installment->vat_number;
            }
            }

        }
       


     ?>
     <div class="header">
   
<table width="400" align="left">
<tr>
    <td width="300" class="blue_bg">
             <div style="text-align:left;background-color:#cee6f7;float:left !important;" class="blue_bg"></div>
    </td>
    <td>  <div style="text-align:right;float:left;" class="blue_bg"><img width="196" height="24" src="<?php echo base_url() ?>assets/images/homepage/invoice-logo.png"></div></td>
</tr>
</table>
    <?php 
        
        if(isset($billing_address['new_contact']->country)){
            
            $country_info = $this->payment_model->get_country_by_country_code($billing_address['new_contact']->country);
            $street = $billing_address['new_contact']->street;
            $ort_and_land = $billing_address['new_contact']->city;
            $country = $country_info->country_name;
        }else{

            
            
            $street = $user_det->street_and_number;
            $ort_and_land = $user_det->city;
            $country = $user_det->country_name;
        }

        

     
     ?>   

     </div>    
    <table style="font-size:10">
        <tr>
            <td style="text-align:left;margin-left:30px;" width="500">
            <div class="user_info" style="line-height:8px;">
     <p><?php echo $attributes['customer']['firstName'].' '.$attributes['customer']['lastName']; ?></p>
     
     <p><?php echo $street; ?></p>
     <p><?php echo $user_det->zipcode." ".$ort_and_land; ?></p>
     <p><?php echo $country; ?></p>
     </div>


     <p><b>Rechnungsnummer: <?php echo $attributes['id']  ?>-<?php echo date('Y'); ?></b></p>
     <?php if($vat_number!=''): ?>
     <p><b>USt-Id.-Nummer : <?php echo $vat_number ;?></b></p>
 <?php endif; ?>
     <p style="text-align:right;">Datum <?php echo date('Y-m-d'); ?></p>
     <p><?php  if($user_det->gender=='MR'){
              echo "Sehr geehrter Herr ".$attributes['customer']['lastName'].',';
            }else{
              echo "Sehr geehrte Frau ".$attributes['customer']['lastName'].',';
            }
        ?></p>

     <p><?php 
     echo "hiermit erlauben wir uns die von Ihnen gebuchten Leistungen in Rechnung zu stellen.<br>"; ?></p>
     <p><?php echo "Nutzung des physio-to-go Online Portals ab:"; ?></p> 
    
        
        <p><?php echo '<b>'.date('d/m/Y',$last_installment->created_date).'</b>'; 
                    if($last_installment->transaction_type=='1'){
                        echo " for 90 Tage";
                        $net_amount = 900;
                    }else{
                        echo " for 30 Tage";
                        $net_amount = 300;
                    }
        ?></p>

        <p>Gesamt netto: <?php echo '<b>'.number_format($net_amount, 2, ',', '.').'€</b>'; ?></p>
        <p>zzgl. <?php echo $last_installment->vat_rate; ?> % USt. </p>
        <p>Gesamt brutto: <?php echo '<b>'.number_format($attributes['amount'], 2, ',', '.').'€</b>'; ?></p>
        <p><?php echo $tax_liablity; ?></p>
        <p></p>
        <p></p>

        <?php $payment_type=$attributes['paymentInstrumentType']; ?>
        <p><?php if($payment_type=='credit_card'){
             
                $card_details = $attributes['creditCardDetails'];
                
                echo "Der fällige Rechnungsbetrag wurde vereinbarungsgemäß Ihrer Kreditkarte mit der Kartennummer ". $card_details->maskedNumber ." belastet.";                 
            }else{
                echo "Der fällige Rechnungsbetrag wurde vereinbarungsgemäß Ihrem PayPal-Konto belastet.";
            } ?></p>

            <p style="font-size:7"><?php echo "Rechnung gemäß unserer gültigen Allgemeinen Geschäftsbedingungen. Diese finden Sie im Internet unter www.physio-to-go.de/AGB." ?></p></td>
        </tr>
    </table>
     
            <p></p>
            <p></p>
            <p></p>
            <p></p>
            <p></p>
            <p></p>
          <div class="footer" style="background-color:#cee6f7">
           <table style="padding:8px">
               <tr>
                 <td style="font-size:7">
                        <b>web physio 2.0 GmbH</b><br>
                        Grünbergweg 5<br>
                        89426 Wittislingen<br>
                        Deutschland</td>
                   <td style="font-size:7">
                        Amtsgericht Augsburg<br>
                        HRB 29691<br>
                        GF: Juliane Lemmer<br>
                        USt-Id.-Nr. DE 300638871</td>
                   <td style="font-size:7">
                        Bankverbindung:<br>
                        IBAN: DE26 7225 1520 0010 2579 77<br>
                        BIC: BYLADEM1DLG<br>
                        Kreissparkasse Dillingen a. d. Donau</td>
               </tr>
           </table>   
          </div>  