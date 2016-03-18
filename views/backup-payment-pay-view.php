<?php if($paid): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="error">You have been already subscribed.</div>
        </div>
    </div>
<?php else: ?>

    <script>
    var vatRate,amtBeforeVat,total,totalVat;
    var paymentUtilities;
    var stepTrigger;
    var stepContainer;
    var origin = '<?php echo $origin->country->cca2;?>';
    var country_code = '<?php echo $user_details->country_code;?>'; //user's country code for billing
    var country_name = '<?php echo $user_details->country_name;?>'; //user's country code for billing
    var subscrptionAmt = '<?php echo INTIAL_SUBSCRIPTION_AMT;?>';
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
        $(".payment-details li a").on('click',function(){
            var curId = $(this).parent().attr('id');

            $(".payment-details li").removeClass();
            $(this).parent().addClass('active-'+curId);
            $(".paybox").hide();
            if(curId == 'paypal' ) {
                $("#paypal-box").show();
            }else {
                 $("#card-box").show();
            }
            paymentMethod = curId;
            return false;
        })

            
            paymentUtilities = {
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

                if (context.parent().hasClass('allow')) {
                    var currentStep = context.parent('li').index();
                    var nextStep = currentStep;
                    var contextClass = context.parent('li').attr('class');
                    isValid = true;

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
                        }

                     }

                }

                //show next steps after form validation
                if (isValid) {
       
                    //steps
                    if ($.inArray(contextClass, classes) !== -1) {
                        //titles
                        
                       if(formId != "cost-summary") {
                            stepTrigger.find('a').removeClass("active");
                            stepTrigger.eq(nextStep).find('a').addClass("active");
                            stepTrigger.removeClass("allow");
                            stepTrigger.eq(currentStep).addClass("allow").prevAll().addClass("allow");
                        }
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
                $.ajax({
                    url: js_base_url + 'payment/create_sms_token',
                    type: "POST",
                    dataType: "json",
                    data: postdata,
                    beforeSend: function(xhr) {
                        $("#"+formId).closest('.step').find(".spinner-vat").show();
                    },
                    success: function(resp) {
                       $("#"+formId).closest('.step').find(".spinner-vat").hide();
                        console.log("Lookup Api: Success" + resp);
                        //console.log(resp.success);
                           if (!resp.success) {
                            $("#"+formId).closest('.step').find(".mobile-not-valid").show().html('Die Verifizierung via Mobiltelefon war nicht erfolgreich. Bitte versuchen Sie es noch einmal oder fahren Sie mit der manuellen Verifizierung fort. ' + (resp));
                           } else {
                            $("#"+formId).closest('.step').find(".mobile-not-valid").show().html("Enter the token sent in your mobile");
                        }

                    },
                    error: function(e, m, err) {
                        console.log("Lookup Api: Error: " + m + " Exp: " + err);

                    }
                });
                                    
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
                            $.ajax({
                                url: js_base_url + 'payment/verify_sms_token',
                                type: "POST",
                                dataType: "json",
                                data: postdata,
                                beforeSend: function(xhr) {
                                    $("#"+formId).closest('.step').find(".spinner-vat").show();
                                },
                                success: function(resp) {
                                   $("#"+formId).closest('.step').find(".spinner-vat").hide();
                                    console.log("Lookup Api: Success" + resp);
                                    smsverification = resp;

                                       if (!resp) {
                                            $("#"+formId).closest('.step').find(".mobile-not-valid").show().html('Die Verifizierung via Mobiltelefon war nicht erfolgreich. Bitte versuchen Sie es noch einmal oder fahren Sie mit der manuellen Verifizierung fort.');
                                       } else {
                                            //take the user to next step
                                            paymentUtilities.menuHandler(3,"#step-3");

                                        //$("#"+formId).closest('.step').find(".mobile-not-valid").show().html("Token verified");
                                    }

                                },
                                error: function(e, m, err) {
                                    console.log("Lookup Api: Error: " + m + " Exp: " + err);

                                }
                            });
                    
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

            }

             $("#agreebillingaddress").html(zip + ' ' + city + ' ' + country_name); //set the billing address for manual verification
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
                $.ajax({
                    url: js_base_url + 'payment/get_taxinfo',
                    type: "POST",
                    dataType: "json",
                    data: postdata,
                    beforeSend: function(xhr) {
                        $("#"+formId + " .spinner-vat").show();
                    },
                    success: function(resp) {
                        $("#"+formId + " .spinner-vat").hide();
                        console.log("Lookup Api: Success" + resp);
                        //console.log(resp);
                        if (resp.countryerror) { //for practice
                            $("#"+formId + " .not-valid").html("UstID Nr nicht gültig").show();
                        } else if(country_code != resp.transaction.tax_country_code && userType == 1) { //for practice
                            $("#"+formId + " .not-valid").html("Billing address and practice registered country doesn't match").show();
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
                                paymentUtilities.menuHandler(2,"#step-2-1");

                            }
                        }

                    },
                    error: function(e, m, err) {
                        console.log("Lookup Api: Error: " + m + " Exp: " + err);

                    }
                });

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
                $.ajax({
                    url: js_base_url + 'payment/verify_voucher',
                    type: "POST",
                    dataType: "json",
                    data: postdata,
                    beforeSend: function(xhr) {
                        $("#"+formId + " .spinner-vat").show();
                    },
                    success: function(resp) {
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

                    },
                    error: function(e, m, err) {
                        console.log("Lookup Api: Error: " + m + " Exp: " + err);

                    }
                });

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
               
                var chk = $("#manual").prop('checked');
                manualVerification = chk;
                if(!chk) {
                    alert("Please check the box");
                    return false;
                } else {
                    //take the user to next step
                     paymentUtilities.menuHandler(2,"#step-2-2");
                }

            }else if ($(this).attr('id') == 'manualagreetrigger') {
                var chk = $("#chkmanualagree").prop('checked');
                if(!chk) {
                    alert("Please check the box");
                    return false;
                } else {
                    //take the user to next step
                    paymentUtilities.menuHandler(3,"#step-3");
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
                ABONNIEREN / RECHNUNGSADRESSE
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
        <h2>ABONNIEREN</h2>

        <!-- Payment Navigation -->
        <div class="payment-navigation">
            <ul>
                <li id="purchase" class="allow"><a class="active" href="#">Purchase</a></li>
                <li id="billing"><a href="#">Rechnungsadresse</a></li>
                <li id="vat"><a href="#">Umsatzsteuer</a></li>
                <li id="voucher"><a href="#">Gutschein</a></li>
                <li id="summary"><a href="#">Übersicht</a></li>
                <li id="payment"><a href="#">Zahlungsinformationen</a></li>
                <li id="confirm"><a href="#">Confirmation</a></li>
            </ul>
        </div>
        <!-- End Payment Navigation -->

        <div class="container-fluid payment-cnt-bg">

            <!-- Payment Physio Kauf -->
            <div class="payment-physio-kauf purchase step" id="step-0">
                <form action="" id="payment-info">
                    <div class="col-md-6 col-md-offset-3 text-center">
                        <div class="white-bg payment-white-bg">
                            <h2>PHYSIOTOGO ABONNIEREN</h2>
                            <p>Um Zugriff auf die komplette Bandbreite der PhysioToGo Funktionen zu bekommen, müssen Sie zunachst ein Abonnement von mindestens 3 Monaten zu einem Preis von €xx,xx pro Monat aktivieren.</p>
                        </div>

                        <button type="button" class="blue-btn">JETZT KAUFEN</button> 

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
                            <label for=""><span>Rechnungsadresse wie Kontaktadresse</span></label>
                        </fieldset>
                        <div class="rech">
                            <h3><?php echo $user_details->gender . ' ' . 
                            $user_details->first_name. ' '.
                            $user_details->last_name;?> </h3>
                            <p> <?php echo $user_details->company_name;?>
                            <br><?php echo $user_details->street_and_number;?>
                            <br><?php echo $user_details->zipcode;?> <?php echo $user_details->city;?>
                            <br><?php echo $user_details->country_name;?></p>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <fieldset class="form-radio">
                          <input id="billing_tgr" type="radio" name="billing" value='1'>
                            <label for=""><span>Rechnungsadresse wie Kontaktadresse</span></label>
                        </fieldset>
                        <fieldset class="form-s">
                            <label for=""><span>Anrede</span></label>
                            <select name="" id="">
                                <option value="">bitte wählen</option>
                                <option value="">bitte wählen</option>
                                <option value="">bitte wählen</option>
                            </select>
                        </fieldset>
                        <fieldset class="form-i">
                            <label for=""><span>Vorname*</span></label>
                            <input type="text" name="first_name" class='required'>
                        </fieldset>
                        <fieldset class="form-i">
                            <label for=""><span>Nachname*</span></label>
                            <input name="last_name" type="text" class='required'>
                        </fieldset>
                        <fieldset class="form-i">
                            <label for=""><span>Unternehmen</span></label>
                            <input type="text" name="company">
                        </fieldset>
                        <fieldset class="form-st">
                            <label class="label-1" for=""><span>Straße*</span></label>
                            <input class="input-1 required" name="street" type="text">
                            <label class="label-2"><span>Nr.</span></label>
                            <input class="input-2" type="text">
                        </fieldset>
                        <fieldset class="form-st">
                            <label class="label-1" for=""><span>Stadt*</span></label>
                            <input class="input-1 required" name="city" type="text">
                            <label class="label-2"><span>PLZ*</span></label>
                            <input name="zip" class="input-2 required" type="text">
                        </fieldset>
                        <fieldset class="form-i">
                            <label for=""><span>Land</span></label>
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

                    <div class="col-md-12 text-center margin-tp-30">
                        <a href="#" class="blue-btn">WEITER</a>
                    </div>
                </form> 
            </div> 
            <!-- End Billing Address -->   

            <!-- User Type -->
            <div class="payment-vat user-type vat step" id="step-2">
                
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <p>Um die Anforderungen der EU Umsatzsteuergesetze für Digitale Produkte zu erfüllen, müssen wir Ihren relevanten Umsatzsteuersatz verifizieren. Bitte wählen Sie unten den entsprechenden Bereich aus.</p>

                        <ul>
                            
                            <li>
                                <form action="" id="vat_step_pat">
                                <fieldset class="form-radio">
                                    <input type="radio" id="pat" checked="checked" name="usertype" value="0">
                                    <label for=""><span>Privatperson oder nicht registriert für EU Umsatzsteuer</span></label>
                                </fieldset>
                                <div class="vat-cnt">
                                    <p>Um den richtigen Umsatzsteuersatz festlegen zu können, müssen wir Ihren Geschäftssitz verifizieren.</p>
                                </div>
                                <button type="button" class="black-btn chckvat">VERIFIZIEREN</button>
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
                                    <input type="radio" id="pra" name="usertype" value="1">
                                    <label for=""><span>Registriert für EU Umsatzsteuer</span></label>
                                </fieldset>
                                <div class="vat-cnt">                                
                                    <p>Bitte geben Sie hier Ihre Umsatzsteueridentifikationsnummer ein</p>
                                    <input type="text" name="vatno" class="required" placeholder="UstID Nr. eingeben">
                                </div>

                                <button type="button" class="black-btn chckvat">VERIFIZIEREN</button>
                                <div class="clearfix"></div>
                                <div class="spinner spinner-vat">
                                    <div class="rect1"></div>
                                    <div class="rect2"></div>
                                    <div class="rect3"></div>
                                </div>
                                <div class="clearfix"></div>

                                <div class="not-valid">UstID Nr nicht gültig</div>
                            </form>
                            </li>
                       
                        </ul>
                    </div>
                
            </div>

                <!-- Mobile verification -->  
                    <div class="payment-vat-non-eu step" id="step-2-1">
                      
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <p>Um die Anforderungen der EU Umsatzsteuergesetze für Digitale Produkte zu erfüllen, müssen wir Ihren relevanten Umsatzsteuersatz verifizieren. Bitte wählen Sie unten den entsprechenden Bereich aus.</p>
                    </div>

                    <div class="col-md-10 col-md-offset-1 text-center">          

                        <div class="white-bg payment-white-bg">
                            <p>Es scheint, dass Sie sich gerade in dem Land mit Ihrem Geschäftssitz aufhalten. Damit wir die Umsatzsteuerregelungen der EU erfüllen können, bitten wir Sie folgende Angabe zu bestätigen.</p>

                            <div class="row">
                                <div class="col-md-8 col-md-offset-2 sales-tax-form-cnt">
                                   
                                    <fieldset class="payment-input-wrap">
                                    <form action="" id="smstoken">  
                                        <label for="" class="callingcode">+49</label>
                                        <input type="text" class="required" name="handynummer" placeholder="Handynummer">
                                        <button type="button" id = "tokentrigger" class="active">SEND</button>
                                    </form>
                                         <form action="" id="smsverify">  
                                        <input type="text" class="required" name="token" placeholder="Verifizierungscode">
                                        <button type="button" id="verifytrigger" class="active">APPLY</button>
                                    </form>
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
                                        <label for=""><span>Ich habe kein Handy in diesem Land oder meine Verifizierung via Mobiltelefon war nicht erfolgreich.</span></label>
                                    </fieldset>
                               

                                </div>
               
                            </div>

                            <button type="button" id= "manual_trigger" class="blue-btn">MANUELLE VERIFIZIERUNG</button>

                            <div class="clearfix"></div>

                            <div class="not-valid mobile-not-valid">Your mobile verification was unsuccessful, please try again or continue with manual verification.</div>

                        </div>

                    </div>
                
            </div>


                    <div class="payment-vat-non-eu step" id="step-2-2">
                <!--<form action="" id="manualagree"> -->           
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <h3>Umsatzsteuer Details</h3>
                        <p>Um die Anforderungen der EU Umsatzsteuergesetze für Digitale Produkte zu erfüllen, müssen wir Ihren relevanten Umsatzsteuersatz verifizieren. Bitte wählen Sie unten den entsprechenden Bereich aus.</p>
                    </div>

                    <div class="col-md-10 col-md-offset-1 text-center">          

                        <div class="white-bg payment-white-bg">
                            <p>Es scheint, dass Sie sich gerade in dem Land mit Ihrem Geschäftssitz aufhalten. Damit wir die Umsatzsteuerregelungen der EU erfüllen können, bitten wir Sie folgende Angabe zu bestätigen.</p>

                            <div class="row">
                                <div class="col-md-8 col-md-offset-2 sales-tax-form-cnt aline-center">
                                    <br>
                                    <fieldset class="form-radio">
                                        <input type="checkbox" id="chkmanualagree" value="1">
                                        <label for=""><span>Hiermit bestätige ich, dass mein Firmensitz in <strong id="agreebillingaddress">76105 Junghilz Österreich</strong> <strong>ist.</strong></label>
                                    </fieldset>
                                </div>
                            </div>
                            
                            
                            <p>Falls Sie noch Fragen zu diesem Verfahren haben, kontaktieren Sie bitte unseren <a href="#">Kundenservice.</a></p>

                        </div>

                    </div>

                    <div class="col-md-12 text-center">
                        <button type="button" class="blue-btn margin-tp-30" id="manualagreetrigger">WEITER</button>
                    </div>

               <!-- </form>--> 
            </div>
            <!-- End Payment VAT NON EU -->


            <!-- User Type -->  

            <!-- Vat Rate -->
            <div class="payment-confirmation vat-rate step" id="step-3">
                <form action="">
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <p>Um die Anforderungen der EU Umsatzsteuergesetze für Digitale Produkte zu erfüllen, müssen wir Ihren relevanten Umsatzsteuersatz verifizieren. Bitte wählen Sie unten den entsprechenden Bereich aus.</p>

                        <br>

                        <p>Unser System hat folgende Informationen zur Umsatzsteuer für Sie verifiziert:</p>

                        <div class="row">
                            <div class="col-md-8 col-md-offset-2 white-bg payment-white-bg">
                                <p id="vat-country">Geschäftssitz: <strong>Austria</strong></p>
                                <p id="vat-rate">Umsatzsteuersatz: <strong>20%</strong></p>
                            </div>
                        </div>

                        <p>Falls dies nicht zutrifft oder Sie sonst noch Fragen haben, kontaktieren Sie bitte unseren Kundenservice.</p>
                    </div>

                    <div class="col-md-12 text-center margin-tp-30">
                        <button class="blue-btn">WEITER</button>
                    </div>

                </form>
            </div>
            <!-- Vat Rate -->  

              <!-- Payment Voucher -->
            <div class="payment-voucher voucher step" id="step-4">
                <form action="" id="voucher-form">
                    <div class="col-md-6 col-md-offset-3 text-center">
                        <div class="white-bg payment-white-bg">
                            <p>Gutschein einlösen</p>

                            <fieldset>
                                <input name="vc" class="required" type="text" placeholder="ZXC234RT4V">
                                <button type="button" class="black-btn chcvoucher">EINLÖSEN</button>
                                       <div class="clearfix"></div>
                                <div class="spinner spinner-vat spinner-voucher">
                                    <div class="rect1"></div>
                                    <div class="rect2"></div>
                                    <div class="rect3"></div>
                                </div>
                                <div class="clearfix"></div>
                                <div class="not-valid">Incorrect Voucher</div>
                            </fieldset>
                        </div>

                        <p>GESAMTE ERMÄSSIGUNG</p>
                        <p id="voucher-amt"><strong>€ 0.00</strong></p>

                        <a href="#" id= "calculate" class="blue-btn margin-tp-30">WEITER</a>
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
                                    <td align="left">Subscription Charge</td>
                                    <td align="right"><?php echo CURRENCY_SYMBOL. ''.INTIAL_SUBSCRIPTION_AMT;?></td>
                                </tr>
                                <tr>
                                    <td align="left">Ermäßigung</td>
                                    <td align="right">-<?php echo CURRENCY_SYMBOL;?><span id="coupon_discount"></span></td>
                                </tr>
                                <tr>
                                    <td align="left">Total before VAT</td>
                                    <td align="right"><?php echo CURRENCY_SYMBOL;?><span id="beforeVatAmt"></span></td>
                                </tr>
                                <tr>
                                    <td align="left">VAT (<span id="tax_rate"></span>) <span id="vatNumberInfo"></span></td>
                                    <td align="right">+<?php echo CURRENCY_SYMBOL;?><span id="taxAmt"></span></td>
                                </tr>
                                <tr>
                                    <td align="left"><strong>Order Total</strong></td>
                                    <td align="right"><strong><?php echo CURRENCY_SYMBOL;?><span id="totalAmt" style="margin-left:0"></span></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="col-md-12 text-center margin-tp-30">
                        <button class="blue-btn">WEITER</button>
                    </div>

                </form>
            </div>
            <!-- End Payment Summary -->

                 <!-- Payment details -->
            <div class="payment-details payment step" id="step-6">
                <form action="" method="post" id="braintree-payment-form">
                    <div class="col-md-6 col-md-offset-3 white-bg payment-white-bg text-center">
                        <ul>
                            <!--<li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card1.png" alt="">
                                </a>
                            </li>-->
                            <li id="amexp">
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card2.png" alt="">
                                </a>
                            </li>
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
                            <li id="visa">
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
                                    <label for="">Name des Karteninhabers</label>
                                    <input class="" type="text" data-encrypted-name="owner">
                                </fieldset>
                            </div>`
                            <div class="col-md-4">
                                <fieldset>
                                    <label for="">Kartennummer</label>
                                    <input type="text" size="20" autocomplete="off" data-encrypted-name="number" id="ccnum">
                                </fieldset>
                            </div>
                            <div class="col-md-3">
                                <fieldset class="exp-date">
                                    <label for="">Ablaufdatum</label>
                                     <select data-encrypted-name="month" class="form-control">
            <?php for($i=1; $i<13;$i++) :?>
                <option value="<?php if($i<9) echo '0'; else '' ?><?php echo $i; ?>"><?php if($i<9) echo '0'; else '' ?><?php echo $i; ?></option>
            <?php endfor;?>
        </select> <span>/</span>
                                    <select data-encrypted-name="year" class="form-control">
    <?php for($i=2015; $i<2030;$i++) :?>
    <option value="<?php echo $i;?>"><?php echo $i;?></option>
    <?php endfor;?>
    </select>

                                </fieldset>
                            </div>
                            <div class="col-md-1">
                                <fieldset>
                                    <label class="label-cvv" for="">CVV</label>
                                    <input class="cvv" type="text" size="4" autocomplete="off" data-encrypted-name="cvv" id = "cvvnum">
                                </fieldset>
                            </div>
                             <div class="invalid-card">Ungültige Kartennummer</div>
                        </div>
                        <div id="paypal-box" class="paybox">
                             
                                  <div id="paypal-container"></div>
                                  
                                <script type="text/javascript" src="https://js.braintreegateway.com/v2/braintree.js"></script>
                                <script type="text/javascript">
                                braintree.setup('<?php echo $client_token;?>', "paypal", {
                                  container: "paypal-container",
                                  singleUse: false,
                                  //enableShippingAddress : true,
                                  onPaymentMethodReceived: function (obj) {
                                     //console.log(obj);
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
                                        };
                         

                                    var billingInfo = $("#billing_step").serialize();
                                    var postdata =  "&"+ billingInfo + "&nonce=" + obj.nonce + "&data="+JSON.stringify(data);
                                   
                                    $.ajax({
                                        url: js_base_url + 'payment/do_paypal_payment',
                                        type: "POST",
                                        dataType: "json",
                                        data: postdata,
                                        beforeSend: function(xhr) {
                                            $("#braintree-payment-form").closest('.step').find(".spinner-vat").show();
                                        },
                                        success: function(resp) {
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
                                                    $("#step-7").show();
                                                }

                                        },
                                        error: function(e, m, err) {
                                            console.log("Lookup Api: Error: " + m + " Exp: " + err);

                                        }
                                    });

                                  }
                                });
                                </script>
                              
                                 <div class="invalid-paypal"></div>
                        </div>

                       

                        <div class="total-value">Gesamtbestelltwert:  <?php echo CURRENCY_SYMBOL;?> <span></span></div>
                        <div class="clearfix"></div>
                         <div class="spinner spinner-vat">
                                  <div class="rect1"></div>
                                  <div class="rect2"></div>
                                  <div class="rect3"></div>
                                </div>
                                 <div class="clearfix"></div>
                    </div>

                    <div class="col-md-12 text-center margin-tp-30">
                        <button type="submit" id = "checkout" class="blue-btn">JETZT BEZAHLEN</a>
                    </div>
                </form>
            </div>

            <!-- Payment Confirmation -->
            <div class="pyment-confirmation confirm step" id="step-7">
                <div class="col-md-6 col-md-offset-3 white-bg payment-white-bg text-center">
                    <p>Vielen Dank für Ihre Bestellung.</p>
                    <p>Ihre Rechnungsnummer lautet 12345678</p>
                    <p>Sie können Ihre Rechnung über das My Account Menü aufrufen. In wenigen Minuten sollten Sie zudem eine Bestätigungsemail mit dem Zahlungsbeleg erhalten.</p>
                    <p>Falls Sie noch Fragen zur Transaktion haben, schicken Sie bitte eine Email an <a href="#">payments@physiotogo.de</a>. Bitte denken Sie daran, in</p>
                </div>
                <div class="col-md-12 text-center">
                    <a id = "gotodash" href="<?php echo base_url().'patient/dashboard';?>" class="blue-btn margin-tp-30">ZURÜCK ZUR HAUPTSEITE</a>
                </div>
            </div>
            <!-- End Payment Confirmation -->

         

            <!--<div class="payment-details paypal" style="display:none">
                <form action="">
                    <div class="col-md-6 col-md-offset-3 white-bg payment-white-bg text-center">
                        <ul>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card1.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card2.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card3.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card4.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card5.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card6.png" alt="">
                                </a>
                            </li>
                        </ul>

                        <div class="row">
                            <div class="col-md-4">
                                <figure class="paypal-logo">
                                    <img src="<?php echo base_url()?>assets/images/homepage/paypal.png" alt="">
                                </figure>
                            </div>
                            <div class="col-md-4">email@email.com</div>
                            <div class="col-md-4"><a href="#">Paypal Konto wechseln</a></div>
                        </div>

                        <div class="total-value">Gesamtbestelltwert: € 1,1430.20</div>
                    </div>

                    <div class="col-md-12 text-center margin-tp-30">
                        <button class="blue-btn">JETZT BEZAHLEN</button>
                    </div>
                </form>
            </div>-->
            
            <!-- Payment Processed -->
            <!--<div class="payment-details paypal" style="display:none">
                <form action="">
                    <div class="col-md-6 col-md-offset-3 white-bg payment-white-bg text-center">
                        <ul>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card1.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card2.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card3.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card4.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card5.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card6.png" alt="">
                                </a>
                            </li>
                        </ul>

                        <div class="payment-processed-bg">
                            <div class="processed-cnt">
                                Ihre Paypal Zahlung ist in Bearbeitung
                                <div class="spinner processed-loading">
                                  <div class="rect1"></div>
                                  <div class="rect2"></div>
                                  <div class="rect3"></div>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <button class="blue-btn">CANCEL</button>
                        </div>

                        <div class="total-value">Gesamtbestelltwert: € 1,1430.20</div>
                    </div>

                    <div class="col-md-12 text-center margin-tp-30">
                        <button class="blue-btn">JETZT BEZAHLEN</button>
                    </div>
                </form>
            </div>--><!-- End Payment Processed -->

            <!-- Payment Checkout with PayPal -->
            <!--<div class="payment-details paypal" style="display:none">
                <form action="">
                    <div class="col-md-6 col-md-offset-3 white-bg payment-white-bg text-center">
                        <ul>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card1.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card2.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card3.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card4.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card5.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <img src="<?php echo base_url()?>assets/images/homepage/card6.png" alt="">
                                </a>
                            </li>
                        </ul>

                        <div class="paypal-checkout">
                            <a href="#">
                                <figure>
                                    <img src="<?php echo base_url()?>assets/images/homepage/checkout.png" alt="">
                                </figure>
                            </a>
                        </div>

                        <div class="total-value">Gesamtbestelltwert: <?php echo CURRENCY_SYMBOL;?> <span></span></div>
                    </div>

                    <div class="col-md-12 text-center margin-tp-30">
                        <button class="blue-btn">JETZT BEZAHLEN</button>
                    </div>
                </form>
            </div>--><!-- End Payment Checkout with PayPal -->

            <!-- End Payment details -->

     

            

            <!-- Payment VAT NON EU -->
            <!--<div class="payment-vat-non-eu" style="display:none">
                <form action="">            
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <p>Um die Anforderungen der EU Umsatzsteuergesetze für Digitale Produkte zu erfüllen, müssen wir Ihren relevanten Umsatzsteuersatz verifizieren. Bitte wählen Sie unten den entsprechenden Bereich aus.</p>
                    </div>

                    <div class="col-md-10 col-md-offset-1 text-center">          

                        <div class="white-bg payment-white-bg">
                            <p>Es scheint, dass Sie sich gerade in dem Land mit Ihrem Geschäftssitz aufhalten. Damit wir die Umsatzsteuerregelungen der EU erfüllen können, bitten wir Sie folgende Angabe zu bestätigen.</p>

                            <div class="row">
                                <div class="col-md-8 col-md-offset-2 sales-tax-form-cnt">

                                    <fieldset class="payment-input-wrap">
                                        <label for="" class="callingcode">+49</label>
                                        <input type="text" placeholder="Handynummer">
                                        <button class="active">ABSCHICKEN</button>
                                        <input type="text" placeholder="Verifizierungscode">
                                        <button>ANFORDERN</button>
                                    </fieldset>

                                    <fieldset class="form-radio">
                                        <input type="checkbox">
                                        <label for=""><span>Ich habe kein Handy in diesem Land oder meine Verifizierung via Mobiltelefon war nicht erfolgreich.</span></label>
                                    </fieldset>
                                </div>
                            </div>

                            <button class="blue-btn">MANUELLE VERIFIZIERUNG</button>

                        </div>

                    </div>
                </form> 
            </div>-->

        </div>

    </section>

    <script src="https://js.braintreegateway.com/v1/braintree.js"></script>
        <script>
          var braintree = Braintree.create("MIIBCgKCAQEA5FC3WO90uS71YAAoQqVkVwVAq954DNv36K6uPpuXZwmEwic0JRk3cjNXyr/fBTC9LCzJX416mJ+d8fTjl+u8mTxb1ZooIHDXuDqRbA5iy5pdf3cG2IJYFkDMJI5IaWRi0VNB4+j5YFASf/wSwlMKjJMBl22I+pps2d8ngKS+bFDxOips1QWNXhna58VlzaEzQtAZZSbSJFkWdau2z8Nm6QGWpQk2eNPfP1FKBolDZtpWqrQjNkYXuYKK1TQH5X1knvc7u4C486f4p42x4JpmdSEqE85FE39vQiy1wUiqo2dAqsYoOP1vMwIuOmE3uV3I/JBtQlopqTaz4Eguko1gDQIDAQAB");
          braintree.onSubmitEncryptForm('braintree-payment-form',function(e){

             e.preventDefault();
                var formId = "braintree-payment-form";

                var billingInfo = $("#billing_step").serialize();
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
                    };
                          var postdata = $("#"+formId).serialize()+ "&"+billingInfo+"&tot=" + total+"&data="+JSON.stringify(data);
                                //var postdata = $("#"+formId).serialize();
                                $.ajax({
                                    url: js_base_url + 'payment/checkout',
                                    type: "POST",
                                    dataType: "json",
                                    data: postdata,
                                    beforeSend: function(xhr) {
                                        $("#"+formId + " .spinner-vat").show();
                                    },
                                    success: function(resp) {
                                       //console.log(resp);
                                       $("#"+formId + " .spinner-vat").hide();
                                       if(resp.error) {
                                            $("#"+formId + " .invalid-card").show().html(resp.msg);
                                            paymentUtilities.enableFormElem("#braintree-payment-form");
                                        } else if(resp.success) {
                                            //paymentUtilities.hideShow($("#" + formId + " .blue-btn"));

                                            stepTrigger.find('a').removeClass("active");
                                            stepTrigger.eq(6).find('a').addClass("active");
                                            stepTrigger.removeClass("allow");
                                            //stepTrigger.eq(6).addClass("allow").prevAll().addClass("allow");
                                            stepContainer.hide();
                                            $("#step-7").show();


                                        }
                                        
                                    
                                    },
                                    error: function(e, m, err) {
                                        console.log("Lookup Api: Error: " + m + " Exp: " + err);
                                    }
                                });


            
          });

               
        </script>
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
<?php endif;?>
