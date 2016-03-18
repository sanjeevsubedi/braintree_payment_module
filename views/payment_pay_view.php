<?php  
    $this->lang->load("payment");
    $this->lang->load("registeration");
      $subscription_amt = $is_practice ? INTIAL_SUBSCRIPTION_AMT : INTIAL_SUBSCRIPTION_AMT_PATIENT;
?>
<?php if($paid): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="error"><?php echo lang("payment.already_subscribed"); ?></div>
        </div>
    </div>
<?php else: ?>

    <script>
    var vatRate,amtBeforeVat,total,totalVat;
    var paymentUtilities;
    var stepTrigger;
    var stepContainer;
    var origin = '<?php echo $origin->country->cca2;?>';
    //var origin = 'NP';
    var country_code = '<?php echo $user_details->country_code;?>'; //user's country code for billing
    var country_name = '<?php echo $user_details->country_name;?>'; //user's country code for billing
    //var subscrptionAmt = '<?php echo INTIAL_SUBSCRIPTION_AMT;?>';
    
    var subscrptionAmt = '<?php echo $subscription_amt;?>';
    var zip = '<?php echo $user_details->zipcode;?>';
    var city = '<?php echo $user_details->city;?>';
    var userType;
    var billingAddress;
    var vatField;
    var manualVerification = 0; //not manual verification
    var smsverification = 0;
    var smstoken;
    var mobielNumber;
    var paymentMethod = 'visa'; //default
    var discount = 0;
    var coupon = "";
    var paypalNonce;

    var first_name = '<?php echo $user_details->first_name;?>';
    var last_name = '<?php echo $user_details->last_name;?>';
    var company_name = '<?php echo $user_details->company_name;?>';
    var street_name = '<?php echo $user_details->street_and_number;?>';
    var street_num = '<?php echo $user_details->street_num;?>';
    var street_add = street_name + ' ' + street_num;

    var EU = ["AT","BE","BG","CY","CZ","DK","DE","EE","EL","GR","ES","FI","FR","HR","IT","LV","LT","LU","HU","IE","MT","NL","PL","PT","RO","SI","SK","SE","UK","GB"];


    $(document).ready(function() {

        var trans = null;
        var isValid = false;

        stepTrigger = $(".payment-navigation li");
        stepContainer = $(".step");
        var isValid = true;
        


        stepContainer.hide();
        stepContainer.eq(0).show();
        $(".not-valid, .spinner-vat, .invalid-card, #paypal-box").hide();

        $("#billing_step").validate({});
        $("#vat_step_pra").validate({});
        $("#voucher-form").validate({});
        $("#smstoken").validate({});
        $("#smsverify").validate({});
        

        stepTrigger.find('a').on('click', function() {
            return false;
        }); 

        /*switch payment methods*/
        var wip = false;
        $(".payment-details li a").on('click',function(){
            var curId = $(this).parent().attr('id');

            $(".payment-details li").removeClass();
            $(this).parent().addClass('active-'+curId);
            //$(".paybox").hide();

            if(curId == 'paypal') {
                 $("#card-box").hide();
                $("#paypal-box").show();
            }else {
                $("#card-box").show();
                $("#paypal-box").hide();
                 $("#checkout").removeClass("processPaypal");
            }


            paymentMethod = curId;
            return false;
        })
    
            
            paymentUtilities = {

                processPayPal: function() {
                    var data = {
                        'user_type':userType,
                        'vat_no' : vatField,
                        'vat_rate': vatRate,
                        'sms_verification': {'number':mobielNumber,'verification_code':smstoken,'status':smsverification},
                        'manual_verification': manualVerification,
                        'discount': discount,
                        'total': total,
                        'payment_method': paymentMethod,
                        'origin': origin,
                        'coupon': coupon,
                        'owner': '', //no credit name over here
                        'nonce': paypalNonce, 
                    };
                             
                    var billingInfo = $("#billing_step").serialize();
                    var postdata =  "&"+ billingInfo + "&nonce=" + paypalNonce+ "&data="+JSON.stringify(data);

                     paymentAjaxBase('do_paypal_payment',postdata, $("#braintree-payment-form").closest('.step').find(".spinner-vat"),function(resp,error){

                            $("#braintree-payment-form").closest('.step').find(".spinner-vat").hide();
                            console.log("Lookup Api: Success" + resp);
                            console.log(resp.success);
                            if (!resp.success) {
                                $(".invalid-paypal").show().html(resp.msg);
                            } else {
                                //next step
                                stepTrigger.find('a').removeClass("active");
                                stepTrigger.eq(6).find('a').addClass("active");
                                stepTrigger.removeClass("allow");
                                stepContainer.hide();
                                $("#tid-pay").html(resp.transaction_id);
                                $("#step-7").show();
                            }
                     })
       
            },
            enableFormElem: function(context) {
                $(context + " input[type='text'], " + context + " select, " + context + " button").prop("disabled", false);
            },
            disableFormElem: function(context) {
                $(context + " input[type='text'], " + context + " select, " + context + " button").prop("disabled", true);
            },
            menuHandler: function(pos,nextstep){
                stepTrigger.find('a').removeClass("active");
                stepTrigger.eq(pos).find('a').addClass("active");
                stepTrigger.removeClass("allow");
                stepTrigger.eq(pos).addClass("allow").prevAll().addClass("allow");
                stepContainer.hide();
                $(nextstep).show();
            },
            hideShow: function(context) {
                var currentContainer = context.closest('.step');
                var classes = ['blue-btn','allow','black-btn chckvat','blue-btn margin-tp-30'];
                var fromStep = false;
                if (context.parent().hasClass('allow')) {
                    var currentStep = context.parent('li').index();
                    var nextStep = currentStep;
                    var contextClass = context.parent('li').attr('class');
                    isValid = true;
                    fromStep = true;

                } else if (context.parent().is( "li" ) && !context.parent().hasClass('allow')) {
                    isValid = false;
                }
                else {
                    var step = currentContainer.attr('id').split('-');
                    var currentStep = parseInt(step[1]);
                    var nextStep = currentStep + 1;
                    var contextClass = context.attr('class');

                    //validate the forms
                    var formId = context.closest('form').attr('id');

                    if (formId) {
                        console.log($("#" + formId).valid());
                        if ($("#" + formId).valid()) {
                            isValid = true;
                        } else {
                            isValid = false;
                        }
                    }

                        // no validation in voucher form
                     if(formId == "voucher-form") {
                        isValid = true;
                        $("#"+formId).validate().resetForm();
                        $("#coupon_discount").html(discount);
                        amtBeforeVat = parseInt(subscrptionAmt) - discount;
                        $("#beforeVatAmt").html(amtBeforeVat);
                        totalVat = (vatRate/100) * amtBeforeVat;
                        total = amtBeforeVat+totalVat;
                        $("#taxAmt").html(totalVat);
                        $("#totalAmt, .total-value span").html(total);

                        if(userType == 1) { //show the vat number only to practice
                            vatField = $("input[name='vatno']").val()
                            $("#vatNumberInfo").html("Umsatzsteuernummer " + country_code + " " + vatField);
                            //Umsatzsteuernummer DE 12345678
                        } else {
                            vatField = 'N/A'; //private person doesn't have vat number
                            $("#vatNumberInfo").html("");
                        }

                     }

                     //credit card form
                       else if(formId == "braintree-payment-form") {
                        isValid = false;

                        var cardBox = $("#card-box");
                        if(cardBox.is(":visible")){
                            var valid = validations.braintreepayment();
                            if(valid) {
                                //process payment
                                //context.attr('disabled','disabled').addClass("disabled-payment");
                                 paymentUtilities.disableFormElem("#braintree-payment-form");
                                $("#braintree-payment-form").submit();
                     
                            }
                        }else {
                            //alert("paypal pay");
                             //process paypal payment
                             if(context.hasClass("processPaypal")) {
                                paymentUtilities.disableFormElem("#braintree-payment-form");
                                paymentUtilities.processPayPal();
                             }
                            
                        }

                     }

                }

                //show next steps after form validation
                if (isValid) {
       
                    //steps
                    if ($.inArray(contextClass, classes) !== -1) {
                        //titles
                        
                      // if(formId != "cost-summary") {
                            stepTrigger.find('a').removeClass("active");
                            // stepTrigger.eq(nextStep).find('a').addClass("active");

                            //console.log(fromStep);

                            if(!fromStep){
                                if(formId == "payment-info" || formId ==  "billing_step") {
                                    stepTrigger.eq(nextStep).find('a').addClass("active");
                                } else {

                                    stepTrigger.eq(nextStep-1).find('a').addClass("active");
                                }   
                            } else {

                                stepTrigger.eq(nextStep).find('a').addClass("active");
                            }

                            //alert(currentStep);
                            stepTrigger.removeClass("allow");
                            stepTrigger.eq(currentStep).addClass("allow").prevAll().addClass("allow");
                        //}
                        stepContainer.hide();
                        $("#step-" + nextStep).show();
                    }
                }
                
            }
        }

          //create sms token

          $("#tokentrigger").on('click', function() {
            //validate the forms
            var formId = $(this).closest('form').attr('id');

            if (formId) {

                if ($("#" + formId).valid()) {

                    var postdata = $(this).closest('form').serialize()+ "&cc=" + country_code;

                    paymentAjaxBase('create_sms_token',postdata,$("#"+formId).closest('.step').find(".spinner-vat"),function(resp,error){

                        if(resp) {

                            $("#"+formId).closest('.step').find(".spinner-vat").hide();
                                console.log("Lookup Api: Success" + resp);
                            //console.log(resp.success);
                            if (!resp.success) {
                                $("#"+formId).closest('.step').find(".mobile-not-valid").show().html('Die Verifizierung via Mobiltelefon war nicht erfolgreich. Bitte versuchen Sie es noch einmal oder fahren Sie mit der manuellen Verifizierung fort. ' + (resp));
                            } else {
                                $("#"+formId).closest('.step').find(".mobile-not-valid").show().html("Bitte geben Sie Ihr SMS Code ein.");
                            }
                        }
                    })
                                    
                } else {
             
                }
            }
        })

              //verify sms token

          $("#verifytrigger").on('click', function() {
             //validate the forms
            var formId = $(this).closest('form').attr('id');

            if (formId) {

                if ($("#" + formId).valid()) {
                      var postdata = $(this).closest('form').serialize();
                      smstoken = $("input[name='handynummer']").val();

                        paymentAjaxBase('verify_sms_token',postdata,$("#"+formId).closest('.step').find(".spinner-vat"),function(resp,error){

                            //if(resp) {

                                $("#"+formId).closest('.step').find(".spinner-vat").hide();
                                console.log("Lookup Api: Success" + resp);
                                smsverification = resp;

                                if (!resp) {
                                    $("#"+formId).closest('.step').find(".mobile-not-valid").show().html('Die Verifizierung via Mobiltelefon war nicht erfolgreich. Bitte versuchen Sie es noch einmal oder fahren Sie mit der manuellen Verifizierung fort.');
                                } else {
                                    //take the user to next step
                                    paymentUtilities.menuHandler(2,"#step-3");

                                //$("#"+formId).closest('.step').find(".mobile-not-valid").show().html("Token verified");
                                }
                            //}

                        })
                    
                } else {

                }
            }
            
            
          })

        //check vat
        $(".chckvat").on('click', function() {

            billingAddress = parseInt($("input[name='billing']:radio:checked").val());
            userType = parseInt($("input[name='usertype']:radio:checked").val());
            var that = $(this);
            //new billing address
            
            if(billingAddress == 1) {
                country_code = $("#billing_step #countryList").val();
                country_name = $("#billing_step #countryList option:selected").text();
                zip= $("input[name='zip']").val();
                city = $("input[name='city']").val();


                first_name = $("input[name='first_name']").val();
                last_name = $("input[name='last_name']").val();
                company_name = $("input[name='company']").val();
                street_name = $("input[name='street']").val();
                street_num = $("input[name='street_num']").val();
                street_add = street_name + ' ' + street_num;



            } else {
                country_code = '<?php echo $user_details->country_code;?>'; //user's country code for billing
                country_name = '<?php echo $user_details->country_name;?>'; //user's country code for billing
                zip = '<?php echo $user_details->zipcode;?>';
                city = '<?php echo $user_details->city;?>';
                first_name = '<?php echo $user_details->first_name;?>';
                last_name = '<?php echo $user_details->last_name;?>';
                company_name = '<?php echo $user_details->company_name;?>';
                street_name = '<?php echo $user_details->street_and_number;?>';
                street_num = '<?php echo $user_details->street_num;?>';
                street_add = street_name + ' ' + street_num;
            }


            if(!country_code) {
                alert("No country added in profile page");
                return false;
            }

             $("#agreebillingaddress").html(' ' + zip + ' ' + city + ' ' + country_name); //set the billing address for manual verification
             helper.getCallingCode(country_code); // get the country calling code
                
            //validate the forms
            var formId = $(this).closest('form').attr('id');

            if (formId) {

                if ($("#" + formId).valid()) {
                    isValid = true;
                } else {
                    isValid = false;

                }
            }
            if (isValid) {
                
                var postdata = $(this).closest('form').serialize()+ "&cc=" + country_code;

                 paymentAjaxBase('get_taxinfo',postdata, $("#"+formId + " .spinner-vat"),function(resp,error){

                        if(resp) {

                                $("#"+formId + " .spinner-vat").hide();
                                console.log("Lookup Api: Success" + resp);
                                //console.log(resp);
                                if (resp.countryerror) { //for practice
                                    $("#"+formId + " .not-valid").html(translations.invalidVatNumber).show();
                                } else if(country_code != resp.transaction.tax_country_code && userType == 1) { //for practice
                                    $("#"+formId + " .not-valid").html(translations.billingAndOriginDoesntMatch).show();
                                } else{

                                    //trans = resp;
                                    //console.log(trans.transaction.tax_entity_name)
                                    //console.log(trans.transaction.transaction_lines[0].tax_rate);
                                    var country = resp.transaction.tax_entity_name;
                                    country = country ? country : country_name; 
                                    
                                    $("#vat-country strong").html(country);
                                    var taxRate = country_code == 'DE' ? 19 : resp.transaction.transaction_lines[0].tax_rate; //german practices are also charged 19% vat
                                    //console.log(trans.transaction.transaction_lines[0]);
                                    vatRate = taxRate != null ? taxRate : 0;
                                    taxRate = taxRate != null ? taxRate + "%" : 'N/A';
                                    $("#vat-rate strong, #tax_rate").html(taxRate);

                                    //origin = 'GB';
                                    if(country_code == origin || userType == 1) {
                                        
                                        $("#"+formId + " .not-valid").hide();
                                         paymentUtilities.hideShow(that);
                                    } else {


                                        if ($.inArray(country_code, EU) !== -1) {
                                            paymentUtilities.menuHandler(2,"#step-2-1");
                                        } else {
                                              paymentUtilities.hideShow(that); // mobile verification not required for non EU countries
                                        }

                                        

                                    }
                                }
                        }
                 })
            }

            return false;
        })


        //check voucher

            $(".chcvoucher").on('click', function() {

            var that = $(this);
            //validate the forms
            var formId = $(this).closest('form').attr('id');

            if (formId) {

                if ($("#" + formId).valid()) {
                    isValid = true;
                } else {
                    isValid = false;

                }
            }
            if (isValid) {
                
                var postdata = $(this).closest('form').serialize();

                 paymentAjaxBase('verify_voucher',postdata, $("#"+formId + " .spinner-vat"),function(resp,error){

                    if(resp) {

                        $("#"+formId + " .spinner-voucher").hide();
                        console.log("Lookup Api: Success" + resp);
                           if (resp.status == "fail") {
                            discount = 0;
                            $("#"+formId + " .not-valid").show();
                            $("#voucher-amt strong").html('€0.00');
                        } else {
                            coupon = $("input[name='vc']").val();
                            $("#voucher-amt strong").html('€'+resp.message);
                            discount = resp.message;
                            $("#"+formId + " .not-valid").hide();
                             paymentUtilities.hideShow(that);
                        }
                    }
                 })
            }

            return false;
        })

         //disable billing info form
        paymentUtilities.disableFormElem("#billing_step");
        paymentUtilities.disableFormElem("#vat_step_pra");

        
        $("#billing_tgr").on('click', function() {
            paymentUtilities.enableFormElem("#billing_step");

        })

        $("#billing_untgr").on('click', function() {
            paymentUtilities.disableFormElem("#billing_step");

        })

        //vat form
        if($("#pra").prop('checked')) {
            paymentUtilities.enableFormElem("#vat_step_pra");
            paymentUtilities.disableFormElem("#vat_step_pat");
        }

        $("#pra").on('click', function() {
            $("#pat").prop('checked',false);
            paymentUtilities.enableFormElem("#vat_step_pra");
            paymentUtilities.disableFormElem("#vat_step_pat");
           
        })

        $("#pat").on('click', function() {
            $("#pra").prop('checked',false);
            paymentUtilities.enableFormElem("#vat_step_pat");
             paymentUtilities.disableFormElem("#vat_step_pra");


        })

        
        $(".blue-btn,.payment-navigation li a").on('click', function() {
            
            if($(this).attr('id') == 'gotodash') {
                return true;
            } else if($(this).attr('id') == 'manual_trigger') {
                 $("#smstoken").validate().resetForm();
                  $("#smsverify").validate().resetForm();
                var chk = $("#manual").prop('checked');
                manualVerification = chk;
                if(!chk) {
                    alert(translations.checkTheBox);
                    return false;
                } else {
                    //take the user to next step
                     paymentUtilities.menuHandler(2,"#step-2-2");
                }

            }else if ($(this).attr('id') == 'manualagreetrigger') {
                var chk = $("#chkmanualagree").prop('checked');
                if(!chk) {
                    alert(translations.checkTheBox);
                    return false;
                } else {
                    //take the user to next step
                    paymentUtilities.menuHandler(2,"#step-3");
                }

            }else {
                paymentUtilities.hideShow($(this));
                return false;
            }
          
        })



    })



    </script>

    <!-- Dashboard Bar -->
    <div class="container-fluid dashboard-bar">
        <div class="col-md-6">
            <div class="das-title">
                <?php echo lang("payment.breadcrumb"); ?> / RECHNUNGSADRESSE
            </div>
        </div>

        <div class="col-md-6">
            <div class="das-btn-wrap">
                <!-- <ul>
                    <li><a class="blue-btn" href="">SPEICHERN</a></li>
                    <li><a class="orange-btn" href="">PATIENT ZUORDNEN</a></li>
                </ul> -->
            </div>
        </div>
    </div>
    <!-- End Dashboard Bar -->

    <section class="white-bg payment-wrap text-center">
        <h2><?php echo lang("payment.breadcrumb"); ?></h2>

        <!-- Payment Navigation -->
        <div class="payment-navigation">
            <ul>
                <li id="purchase" class="allow"><a class="active" href="#"><?php echo lang("payment.purchase"); ?></a></li>
                <li id="billing"><a href="#"><?php echo lang("payment.billiing_address"); ?></a></li>
                <li id="vat"><a href="#"><?php echo lang("payment.sales_tax"); ?></a></li>
                <li id="voucher"><a href="#"><?php echo lang("payment.voucher"); ?></a></li>
                <li id="summary"><a href="#"><?php echo lang("payment.survey"); ?></a></li>
                <li id="payment"><a href="#"><?php echo lang("payment.payment_information"); ?></a></li>
                <li id="confirm"><a href="#"><?php echo lang("payment.conformation"); ?></a></li>
            </ul>
        </div>
        <!-- End Payment Navigation -->

        <div class="container-fluid payment-cnt-bg">
            <!-- loader-->
                <div class="clearfix"></div>
                <div>
                    <div class="spinner spinner-vat">
                        <div class="rect1"></div>
                        <div class="rect2"></div>
                        <div class="rect3"></div>
                    </div>
                </div>
                <div class="clearfix"></div>
            <!-- Payment Physio Kauf -->
            <div class="payment-physio-kauf purchase step" id="step-0">
                <form action="" id="payment-info">
                    <div class="col-md-6 col-md-offset-3 text-center">
                        <div class="white-bg payment-white-bg">
                            <h2><?php echo lang("payment.physiotogo_subscribe"); ?></h2>
                           <?php if( $this->session->userdata('fr_logged_in') ) {
                                $sess_array = get_session_user_info();
                                $user_id = $sess_array['id'];
                                if ( is_self_service($user_id) || is_therapist_assist($user_id)){ ?>

                            <p><?php echo lang("payment.need_to_subscribe_for_3_month_to_use_physio_to_go_patient"); ?></p>
                        <?php } else { ?>
                            <p><?php echo lang("payment.need_to_subscribe_for_3_month_to_use_physio_to_go_practice"); ?></p>
                        
                        <?php } 
                    }
                        ?>
                        </div>

                        <button type="button" class="blue-btn"><?php echo lang("payment.bye_now"); ?></button> 

                    </div>
                </form>
            </div>
            <!-- End Payment Physio Kauf -->

            <!-- Billing Address -->
            <div class="billing-address billing step" id="step-1">
                <form action="" id="billing_step">
                    <div class="col-md-5 col-md-offset-1">
                        <fieldset class="form-radio">
                            <input id="billing_untgr" checked="checked" type="radio" name="billing" value='0'>
                            <label for=""><span><?php echo lang("payment.billing_address_as_contact_old"); ?></span></label>
                        </fieldset>
                        <div class="rech">
                            <h3><?php echo $user_details->gender . ' ' . 
                            $user_details->first_name. ' '.
                            $user_details->last_name;?> </h3>
                            <p> <?php echo $user_details->company_name;?>
                            <br><?php echo $user_details->street_and_number;?>
                                <?php echo $user_details->street_num;?>
                            <br><?php echo $user_details->zipcode;?> <?php echo $user_details->city;?>
                            <br><?php echo $user_details->country_name;?></p>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="fieldset-wrap">
                            <fieldset class="form-radio">
                              <input id="billing_tgr" type="radio" name="billing" value='1'>
                                <label for=""><span><?php echo lang("payment.billing_address_as_contact"); ?></span></label>
                            </fieldset>
                        </div>
                        <div class="fieldset-wrap">
                            <fieldset class="form-s">
                                <label for=""><span><?php echo lang("registeration.salutation"); ?></span></label>
                                <select name="" id="">
                                    <option value=""><?php echo lang("payment.select_one"); ?></option>
                                    <option value=""><?php echo lang("registeration.mr"); ?></option>
                                    <option value=""><?php echo lang("registeration.ms"); ?></option>
                                </select>
                            </fieldset>
                        </div>
                        <div class="fieldset-wrap">
                            <fieldset class="form-i">
                                <label for=""><span><?php echo lang("registeration.first_name"); ?>*</span></label>
                                <input type="text" name="first_name" class='required'>
                            </fieldset>
                        </div>
                        <div class="fieldset-wrap">
                            <fieldset class="form-i">
                                <label for=""><span><?php echo lang("registeration.last_name"); ?>*</span></label>
                                <input name="last_name" type="text" class='required'>
                            </fieldset>
                        </div>
                        <div class="fieldset-wrap">
                            <fieldset class="form-i">
                                <label for=""><span><?php echo lang("registeration.company_name"); ?></span></label>
                                <input type="text" name="company">
                            </fieldset>
                        </div>
                        <div class="fieldset-wrap">
                            <fieldset class="form-st">
                                <label class="label-1" for=""><span><?php echo lang("registeration.street"); ?>*</span></label>
                                <input class="input-1 required" name="street" type="text">
                                <label class="label-2"><span><?php echo lang("registeration.street_num"); ?></span></label>
                                <input class="input-2" type="text" name= "street_num">
                            </fieldset>
                        </div>
                        <div class="fieldset-wrap">
                            <fieldset class="form-st">
                                <label class="label-1" for=""><span><?php echo lang("registeration.city"); ?>*</span></label>
                                <input class="input-1 required stadt-input" name="city" type="text">
                                <label class="label-2"><span><?php echo lang("registeration.zipcode"); ?>*</span></label>
                                <input name="zip" class="input-2 required" type="text">
                            </fieldset>
                        </div>
                        <div class="fieldset-wrap">
                            <fieldset class="form-i">
                                <label for=""><span><?php echo lang("registeration.country"); ?></span></label>
                                <select name="country" id="countryList">
                                               <?php /*
                                        if ( isset($country_list) ){
                                            foreach ( $country_list as $country ) { ?>
                                            <?php if($country->cca2 != "00"):?>
                                                <option value="<?php echo $country->cca2; ?>"><?php echo $country->name; ?></option>
                                            <?php endif;?>
                                            <?php   }
                                        }
                                    */ ?>
                                </select>
                            </fieldset>
                        </div>
                    </div>

                    <div class="col-md-12 text-center margin-tp-30">
                        <a href="#" class="blue-btn"><?php echo lang("payment.next"); ?></a>
                    </div>
                </form> 
            </div> 
            <!-- End Billing Address -->   

            <!-- User Type -->
            <div class="payment-vat user-type vat step" id="step-2">
                
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <p><?php //echo lang("payment.vat_requirement_description"); ?></p>

                        <ul>
                            
                            <li>
                                <form action="" id="vat_step_pat">
                                <fieldset class="form-radio">
                                    <input type="radio" id="pat" <?php echo $is_practice ? '' : 'checked';?> name="usertype" value="0">
                                    <label for=""><span><?php echo lang("payment.not_register_4_eu_vat"); ?></span></label>
                                </fieldset>
                                <div class="vat-cnt">
                                    <p><?php echo lang("payment.need_2_verify_business"); ?></p>
                                </div>
                                <button type="button" class="black-btn chckvat"><?php echo lang("payment.verify"); ?></button>
                            <div class="clearfix"></div>
                                <div class="spinner spinner-vat">
                                    <div class="rect1"></div>
                                    <div class="rect2"></div>
                                    <div class="rect3"></div>
                                </div>
                                <div class="clearfix"></div>
                            </form>
                            </li>


                            <li>
                                <form action="" id="vat_step_pra">
                                <fieldset class="form-radio">
                                    <input type="radio" id="pra" <?php echo $is_practice ? 'checked' : '';?> name="usertype" value="1">
                                    <label for=""><span><?php echo lang("payment.join_4_eu_vat"); ?></span></label>
                                </fieldset>
                                <div class="vat-cnt">                                
                                    <p><?php echo lang("payment.enter_vat_registeration_number"); ?></p>
                                    <input type="text" name="vatno" class="required" value="<?php echo $ustidno;?>" placeholder="UstID Nr. eingeben">
                                </div>

                                <button type="button" class="black-btn chckvat"><?php echo lang("payment.verify"); ?></button>
                                <div class="clearfix"></div>
                                <div class="spinner spinner-vat">
                                    <div class="rect1"></div>
                                    <div class="rect2"></div>
                                    <div class="rect3"></div>
                                </div>
                                <div class="clearfix"></div>

                                <div class="not-valid"><?php echo lang("payment.not_valid_ust_id"); ?></div>
                            </form>
                            </li>
                       
                        </ul>
                    </div>
                
            </div>

                <!-- Mobile verification -->  
                    <div class="payment-vat-non-eu step" id="step-2-1">
                      
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <p><?php //echo lang("payment.vat_requirement_description"); ?></p>
                    </div>

                    <div class="col-md-10 col-md-offset-1 text-center">          

                        <div class="white-bg payment-white-bg">
                            <p><?php echo lang("payment.confirm_following_statement_2_fulfill_eu_vat_rules"); ?></p>

                            <div class="row">
                                <div class="col-md-8 col-md-offset-2 sales-tax-form-cnt">
                                   
                                    <fieldset class="payment-input-wrap">
                                    <div class="col-md-6">
                                    <form action="" id="smstoken">  
                                        <label for="" class="callingcode">+49</label>
                                        <input type="text" class="required" name="handynummer" placeholder="Handynummer">
                                        <button type="button" id = "tokentrigger" class="active"><?php echo lang("payment.send"); ?></button>
                                    </form>
                                </div>
                                 <div class="col-md-6">
                                         <form action="" id="smsverify">  
                                        <input type="text" class="required" name="token" placeholder="Verifizierungscode">
                                        <button type="button" id="verifytrigger" class="active"><?php echo lang("payment.apply"); ?></button>
                                    </form>
                                </div>
                                    </fieldset>
                                                      <div class="clearfix"></div>
                         <div class="spinner spinner-vat">
                                  <div class="rect1"></div>
                                  <div class="rect2"></div>
                                  <div class="rect3"></div>
                                </div>
                                 <div class="clearfix"></div>
                                    
                                    
                                    <fieldset class="form-radio">
                                        <input type="checkbox" id="manual" value="1">
                                        <label for=""><span><?php echo lang("payment.sms_verification_unsuccessful"); ?></span></label>
                                    </fieldset>
                               

                                </div>
               
                            </div>

                            <button type="button" id= "manual_trigger" class="blue-btn"><?php echo lang("payment.manual_verification"); ?></button>

                            <div class="clearfix"></div>

                            <div class="not-valid mobile-not-valid"><?php echo lang("payment.continue_with_manual_verification"); ?></div>

                        </div>

                    </div>
                
            </div>


                    <div class="payment-vat-non-eu step" id="step-2-2">
                <!--<form action="" id="manualagree"> -->           
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <h3><?php echo lang("payment.vat_details"); ?></h3>
                        <p><?php echo lang("payment.vat_requirement_description"); ?></p>
                    </div>

                    <div class="col-md-10 col-md-offset-1 text-center">          

                        <div class="white-bg payment-white-bg">
                            <p><?php echo lang("payment.confirm_following_statement_2_fulfill_eu_vat_rules"); ?></p>

                            <div class="row">
                                <div class="col-md-8 col-md-offset-2 sales-tax-form-cnt aline-center">
                                    <br>
                                    <fieldset class="form-radio">
                                        <input type="checkbox" id="chkmanualagree" value="1">
                                        <label for=""><span><?php echo lang("payment.certify_company_headquarter"); ?></span><strong id="agreebillingaddress"></strong> <!--<strong> ist.</strong>--></label>
                                    </fieldset>
                                </div>
                            </div>
                            
                            
                            <p>Falls Sie noch Fragen zu diesem Verfahren haben, kontaktieren Sie bitte unseren <a href="#">Kundenservice.</a></p>

                        </div>

                    </div>

                    <div class="col-md-12 text-center">
                        <button type="button" class="blue-btn margin-tp-30" id="manualagreetrigger"><?php echo lang("payment.next"); ?></button>
                    </div>

               <!-- </form>--> 
            </div>
            <!-- End Payment VAT NON EU -->


            <!-- User Type -->  

            <!-- Vat Rate -->
            <div class="payment-confirmation vat-rate step" id="step-3">
                <form action="">
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <p><?php //echo lang("payment.vat_requirement_description"); ?></p>

                        <br>

                        <p><?php echo lang("payment.system_has_verified"); ?></p>

                        <div class="row">
                            <div class="col-md-8 col-md-offset-2 white-bg payment-white-bg">
                                <p id="vat-country">Geschäftssitz: <strong></strong></p>
                                <p id="vat-rate">Umsatzsteuersatz: <strong></strong></p>
                            </div>
                        </div>

                        <p><?php echo lang("payment.contact_customer_service_if_any_queries"); ?></p>
                    </div>

                    <div class="col-md-12 text-center margin-tp-30">
                        <button class="blue-btn"><?php echo lang("payment.next"); ?></button>
                    </div>

                </form>
            </div>
            <!-- Vat Rate -->  

              <!-- Payment Voucher -->
            <div class="payment-voucher voucher step" id="step-4">
                <form action="" id="voucher-form">
                    <div class="col-md-6 col-md-offset-3 text-center">
                        <div class="white-bg payment-white-bg">
                            <p><?php echo lang("payment.redeem_voucher"); ?></p>

                            <fieldset>
                                <input name="vc" class="required" type="text" placeholder="">
                                <button type="button" class="black-btn chcvoucher"><?php echo lang("payment.speeding"); ?></button>
                                       <div class="clearfix"></div>
                                <div class="spinner spinner-vat spinner-voucher">
                                    <div class="rect1"></div>
                                    <div class="rect2"></div>
                                    <div class="rect3"></div>
                                </div>
                                <div class="clearfix"></div>
                                <div class="not-valid"><?php echo lang("payment.incorrect_voucher"); ?></div>
                            </fieldset>
                        </div>

                        <p><?php echo lang("payment.total_reduction"); ?></p>
                        <p id="voucher-amt"><strong></strong></p>

                        <a href="#" id= "calculate" class="blue-btn margin-tp-30"><?php echo lang("payment.next"); ?></a>
                    </div>
                </form>
            </div>
            <!-- End Payment Voucher --> 

                   <!-- Payment Summary -->
            <div class="payment-summary summary step" id="step-5">
                <form action="" id="cost-summary">
                    <div class="col-md-6 col-md-offset-3 white-bg payment-white-bg text-center">
                        <table>
                            <tbody>
                                <tr>
                                    <td align="left"><?php echo lang("payment.subscription_charge"); ?></td>
                                    <td align="right"><?php echo CURRENCY_SYMBOL. ''.$subscription_amt;?></td>
                                </tr>
                                <tr>
                                    <td align="left"><?php echo lang("payment.reduction"); ?></td>
                                    <td align="right">-<?php echo CURRENCY_SYMBOL;?><span id="coupon_discount"></span></td>
                                </tr>
                                <tr>
                                    <td align="left"><?php echo lang("payment.total_before_vat"); ?></td>
                                    <td align="right"><?php echo CURRENCY_SYMBOL;?><span id="beforeVatAmt"></span></td>
                                </tr>
                                <tr>
                                    <td align="left"><?php echo lang("payment.vat"); ?> (<span id="tax_rate"></span>) <span id="vatNumberInfo"></span></td>
                                    <td align="right">+<?php echo CURRENCY_SYMBOL;?><span id="taxAmt"></span></td>
                                </tr>
                                <tr>
                                    <td align="left"><strong><?php echo lang("payment.order_total"); ?></strong></td>
                                    <td align="right"><strong><?php echo CURRENCY_SYMBOL;?><span id="totalAmt" style="margin-left:0"></span></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="col-md-12 text-center margin-tp-30">
                        <button class="blue-btn"><?php echo lang("payment.next"); ?></button>
                    </div>

                </form>
            </div>
            <!-- End Payment Summary -->

                 <!-- Payment details -->
            <div class="payment-details payment step" id="step-6">
                <form action="" method="post" id="braintree-payment-form">
                    <div class="col-md-8 col-md-offset-2 white-bg payment-white-bg text-center">
                        <ul>
                            <li id="mastercard">
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card1.png" alt="">
                                </a>
                            </li>
                            <!--<li id="amexp">
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card2.png" alt="">
                                </a>
                            </li>-->
                            <li id="maestro">
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card3.png" alt="">
                                </a>
                            </li>
                            <li id="discover">
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card4.png" alt="">
                                </a>
                            </li>
                            <li id="visa" class="active-visa">
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card5.png" alt="">
                                </a>
                            </li>
                            <li id="paypal">
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card6.png" alt="">
                                </a>
                            </li>
                        </ul>

                        <div class="row paybox" id="card-box">
                            <div class="col-md-4">
                                <fieldset>
                                    <label for=""><?php echo lang("payment.name_of_cardholder"); ?></label>
                                    <input class="" type="text" data-encrypted-name="owner" id="owner">
                                </fieldset>
                            </div>
                            <div class="col-md-4">
                                <fieldset>
                                    <label for=""><?php echo lang("payment.card_number"); ?></label>
                                    <input type="text" size="20" autocomplete="off" data-encrypted-name="number" id="ccnum">
                                </fieldset>
                            </div>
                            <div class="col-md-3">
                                <fieldset class="exp-date">
                                    <label for=""><?php echo lang("payment.expires"); ?></label>
                                     <select id="month" data-encrypted-name="month" class="form-control exp1">
                                        <?php for($i=1; $i<13;$i++) :?>
                                            <option value="<?php if($i<9) echo '0'; else '' ?><?php echo $i; ?>"><?php if($i<9) echo '0'; else '' ?><?php echo $i; ?></option>
                                        <?php endfor;?>
                                    </select> 
                                    <!-- <span>/</span> -->
                                    <select id = "year" data-encrypted-name="year" class="form-control exp2">
                                        <?php for($i=2016; $i<2030;$i++) :?>
                                        <option value="<?php echo $i;?>"><?php echo $i;?></option>
                                        <?php endfor;?>
                                    </select>

                                </fieldset>
                            </div>
                            <div class="col-md-1">
                                <fieldset>
                                    <label class="label-cvv" for=""><?php echo lang("payment.cvv"); ?></label>
                                    <input class="cvv" type="text" size="4" autocomplete="off" data-encrypted-name="cvv" id = "cvvnum">
                                </fieldset>
                            </div>
                             <div class="invalid-card"><?php echo lang("payment.invalid_card_number"); ?></div>
                        </div>
                        <div id="paypal-box" class="paybox">
                            <div id="paypal-container"></div>
                                <div class="invalid-paypal"></div>
                        </div>

                       

                        <div class="total-value"><?php echo lang("payment.total_ordered_value"); ?>  <?php echo CURRENCY_SYMBOL;?> <span></span></div>
                        <div class="clearfix"></div>
                         <div class="spinner spinner-vat">
                                  <div class="rect1"></div>
                                  <div class="rect2"></div>
                                  <div class="rect3"></div>
                                </div>
                                 <div class="clearfix"></div>
                    </div>

                    <div class="col-md-12 text-center margin-tp-30">
                        <button type="submit" id = "checkout" class="blue-btn"><?php echo lang("payment.pay_now"); ?></button>
                    </div>
                </form>
            </div>

            <!-- Payment Confirmation -->
            <div class="pyment-confirmation confirm step" id="step-7">
                <div class="col-md-6 col-md-offset-3 white-bg payment-white-bg text-center">
                    <p>Vielen Dank für Ihre Bestellung.</p>
                    <p>Ihre Rechnungsnummer lautet <span id="tid-pay"></span></p>
                    <p>Sie können Ihre Rechnung im Menü über "Mein Konto" aufrufen. In wenigen Minuten sollten Sie zudem eine Bestätigungsemail mit dem Zahlungsbeleg erhalten.</p>
                    <p>Falls Sie noch Fragen zur Transaktion haben, schicken Sie bitte eine Email an <a href="#">info@physio-to-go.de</a>.</p>
                </div>
                <div class="col-md-12 text-center">
                    <a id ="gotodash" href="<?php echo $redirect_url ?>" class="blue-btn margin-tp-30">ZURÜCK ZUR HAUPTSEITE</a>
                </div>
            </div>
            <!-- End Payment Confirmation -->
            <!--here-->
        </div>

    </section>
        <script>
        $(document).ready(function(){
           //fetch country list
            var postdata='type=add';
              $.ajax({
                    url: js_base_url + 'ajax/get_country_list',
                    type: "POST",
                    dataType: "html",
                    data: postdata,
                    beforeSend: function(xhr) {
                        
                    },
                    success: function(resp) {
                        //console.log(resp['time']);
                            //console.log(resp);
                            $("#countryList").html(resp);

                    },
                    error: function(e, m, err) {
                        console.log("Lookup Api: Error: " + m + " Exp: " + err);

                    }
                });
        });
    </script>
<script src="https://js.braintreegateway.com/v2/braintree.js"></script>
 <script>
          var client = Braintree.create('<?php echo BRAINTREE_CLIENTSIDE_ENCRYPTION_KEY;?>');
          client.onSubmitEncryptForm('braintree-payment-form',function(e){

                e.preventDefault();
                var owner = $("#owner").val();
                var year =  $("#year").val();
                var month =  $("#month").val();
                var cvv_no =  $("#cvvnum").val();
                var cardno =  $("#ccnum").val();

                var formId = "braintree-payment-form";
        
                var tokenclient = new braintree.api.Client({clientToken: "<?php echo $client_token;?>"});
                $("#"+formId + " .spinner-vat").show();
                tokenclient.tokenizeCard({
                        number: cardno,
                        cardholderName: owner,
                        // or expirationMonth and expirationYear
                        expirationMonth: month,
                        expirationYear: year,
                        // CVV if required
                        cvv: cvv_no,
                        // Address if AVS is on
                        billingAddress: {
                            postalCode: zip,
                            firstName: first_name,
                            lastName: last_name,
                            company: company_name,
                            streetAddress: street_add,
                            locality: city,
                            countryCodeAlpha2: country_code, 

                        }
                    }, function (err, nonce) {
                   
                            console.log(nonce);
                            // Send nonce to your server

                            var billingInfo = $("#billing_step").serialize();
                            var owner = $("#owner").val();
                            console.log(billingInfo);
                            var data = {
                                'user_type':userType,
                                'vat_no' : vatField,
                                'vat_rate': vatRate,
                                'sms_verification': {'number':mobielNumber,'verification_code':smstoken,'status':smsverification},
                                'manual_verification': manualVerification,
                                'discount': discount,
                                'total': total,
                                'payment_method': paymentMethod,
                                'origin': origin,
                                'coupon': coupon,
                                'owner': owner,
                                'nonce' : nonce,
                            };
                              var postdata = $("#"+formId).serialize()+ "&"+billingInfo+"&tot=" + total+"&data="+JSON.stringify(data);
                                    //var postdata = $("#"+formId).serialize();
                                   
                                paymentAjaxBase('checkout',postdata, $("#"+formId + " .spinner-vat"),function(resp,error){

                                        //console.log(resp);
                                        $("#"+formId + " .spinner-vat").hide();
                                        if(resp.error) {


                                             if(resp.transaction_code){


                                                switch(resp.transaction_code) {

                                                    case "2000":
                                                        resp.msg = translations.error2000;
                                                    break;

                                                    case "2002":
                                                    case "2003":
                                                    case "2004":
                                                        resp.msg = translations.error2003;  
                                                    break; 
                                                    case "2005":
                                                        resp.msg = translations.error2005;
                                                         break; 
                                                    case "2006":
                                                        resp.msg = translations.error2006;
                                                    break; 
                                                    case "2010":
                                                        resp.msg = translations.error2010;  
                                                    break; 
                                                    case "2012":
                                                        resp.msg = translations.error2012; 
                                                    break;
                                                    default:
                                                        resp.msg = resp.msg;
                                                }

                                            }    




                                            $("#"+formId + " .invalid-card").show().html(resp.msg);
                                            paymentUtilities.enableFormElem("#braintree-payment-form");
                                        } else if(resp.success) {
                                            //paymentUtilities.hideShow($("#" + formId + " .blue-btn"));

                                            stepTrigger.find('a').removeClass("active");
                                            stepTrigger.eq(6).find('a').addClass("active");
                                            stepTrigger.removeClass("allow");
                                            //stepTrigger.eq(6).addClass("allow").prevAll().addClass("allow");
                                            stepContainer.hide();
                                            $("#tid-pay").html(resp.transaction_id);
                                            $("#step-7").show();

                                        }

                                })


                    });

          });

               
        </script>
<script type="text/javascript">
    braintree.setup('<?php echo $client_token;?>', "paypal", {
        container: "paypal-container",
        singleUse: false,
        //enableShippingAddress : true,
        onPaymentMethodReceived: function (obj) {
        //console.log(obj);
        paypalNonce = obj.nonce;
        $("#checkout").addClass("processPaypal");

        }
    });
</script>
<?php endif;?>
