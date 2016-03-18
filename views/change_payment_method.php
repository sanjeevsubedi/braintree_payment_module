<?php 
$this->lang->load("payment");
?>
<script>

var country_code = '<?php echo $user_details->country_code;?>'; //user's country code for billing
var country_name = '<?php echo $user_details->country_name;?>'; //user's country code for billing
var subscrptionAmt = '<?php echo SUBSCRIPTION_AMT;?>'; //monthly subscription amount
var zip = '<?php echo $user_details->zipcode;?>';
var city = '<?php echo $user_details->city;?>';
var paypalNonce;
var paymentMethod = 'visa';
var first_name = '<?php echo $user_details->first_name;?>';
var last_name = '<?php echo $user_details->last_name;?>';
var company_name = '<?php echo $user_details->company_name;?>';
var street_name = '<?php echo $user_details->street_and_number;?>';
var street_num = '<?php echo $user_details->street_num;?>';
var street_add = street_name + ' ' + street_num;
var customerId = '<?php echo $user_details->customer_id;?>';
var vat = 0;
var vatRate = 0;
var discount = 0;
var total = parseInt(subscrptionAmt) + vat;
$(document).ready(function(){
    $(".step").show();
    $(".not-valid, .spinner-vat, .invalid-card, #paypal-box").hide();

                     /*switch payment methods*/
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

 <section class="white-bg payment-wrap text-center">
     <div class="container-fluid payment-cnt-bg">
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
                            </div>`
                            <div class="col-md-4">
                                <fieldset>
                                    <label for=""><?php echo lang("payment.card_number"); ?></label>
                                    <input type="text" size="20" autocomplete="off" data-encrypted-name="number" id="ccnum">
                                </fieldset>
                            </div>
                            <div class="col-md-3">
                                <fieldset class="exp-date">
                                    <label for=""><?php echo lang("payment.expires"); ?></label>
                                     <select id = "month" data-encrypted-name="month" class="form-control exp1">
                                        <?php for($i=1; $i<13;$i++) :?>
                                            <option value="<?php if($i<9) echo '0'; else '' ?><?php echo $i; ?>"><?php if($i<9) echo '0'; else '' ?><?php echo $i; ?></option>
                                        <?php endfor;?>
                                    </select> 
                                    <!-- <span>/</span> -->
                                    <select id = "year"  data-encrypted-name="year" class="form-control exp2">
                                        <?php for($i=2015; $i<2030;$i++) :?>
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
                                <div class="invalid-paypal invalid-card"></div>
                        </div>

                       

                        <div class="total-value"><?php echo lang("payment.total_ordered_value"); ?>  <?php echo CURRENCY_SYMBOL;?> <span><?php echo SUBSCRIPTION_AMT;?></span></div>
                        <div class="clearfix"></div>
                         <div class="spinner spinner-vat">
                                  <div class="rect1"></div>
                                  <div class="rect2"></div>
                                  <div class="rect3"></div>
                                </div>
                                 <div class="clearfix"></div>
                    </div>

                    <div class="col-md-12 text-center margin-tp-30">
                        <button type="submit" id = "checkout" class="blue-btn"><?php echo lang("payment.pay_now"); ?></a>
                    </div>
                </form>
            </div>
        </div>
        </section>
        <script src="https://js.braintreegateway.com/v2/braintree.js"></script>
 <script>
 var paymentUtilities = {

        enableFormElem: function(context) {
            $(context + " input[type='text'], " + context + " select, " + context + " button").prop("disabled", false);
        },
        disableFormElem: function(context) {
            $(context + " input[type='text'], " + context + " select, " + context + " button").prop("disabled", true);
        },
        processPayPal: function() {
            var data = {
                'user_type':'',
                'vat_no' : '',
                'vat_rate': vatRate,
                'sms_verification': '',
                'manual_verification': '',
                'discount': discount,
                'total': total,
                'payment_method': paymentMethod,
                'origin': '',
                'coupon': '',
                'owner': '', //no credit name over here
                'customerId': customerId,
                'nonce': paypalNonce,
            };
                     
            var billingInfo = '';
            var postdata =  "&"+ billingInfo + "&nonce=" + paypalNonce+ "&data="+JSON.stringify(data);

             paymentAjaxBase('add_payment_method',postdata, $("#braintree-payment-form").closest('.step').find(".spinner-vat"),function(resp,error){

                    $("#braintree-payment-form").closest('.step').find(".spinner-vat").hide();
                    console.log("Lookup Api: Success" + resp);
                    console.log(resp.success);
                    if (!resp.success) {
                        $(".invalid-paypal").show().html(resp.msg);
                    } else {
                        //next step
                        $("#tid-pay").html(resp.transaction_id);
                        $("#step-7").show();
                    }
             })

        }
 }

$(document).ready(function(){

      $(".blue-btn").on('click', function() {
        var context = $(this);
            var formId = $(this).closest('form').attr('id');

            var cardBox = $("#card-box");
            if(cardBox.is(":visible")){
                var valid = validations.braintreepayment();
                if(valid) {
                    //process payment
                    //context.attr('disabled','disabled').addClass("disabled-payment");
                     paymentUtilities.disableFormElem("#braintree-payment-form");
                    $("#braintree-payment-form").submit();
                }
            } else {
                //alert("paypal pay");
                 //process paypal payment
                 if(context.hasClass("processPaypal")) {
                    paymentUtilities.disableFormElem("#braintree-payment-form");
                    paymentUtilities.processPayPal();
                 }

            }
            return false;
            })

})


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
                    'user_type':'',
                    'vat_no' : '',
                    'vat_rate': vatRate,
                    'sms_verification': '',
                    'manual_verification': '',
                    'discount': discount,
                    'total': total,
                    'payment_method': paymentMethod,
                    'origin': '',
                    'coupon': '',
                    'owner': owner,
                    'nonce' : nonce,
                    'customerId': customerId,
                };
                  var postdata = $("#"+formId).serialize()+ "&"+billingInfo+"&tot=" + total+"&data="+JSON.stringify(data);
                        //var postdata = $("#"+formId).serialize();
                       
                    paymentAjaxBase('add_payment_method',postdata, $("#"+formId + " .spinner-vat"),function(resp,error){

                            //console.log(resp);
                            $("#"+formId + " .spinner-vat").hide();
                            if(resp.error) {
                                $("#"+formId + " .invalid-card").show().html(resp.msg);
                                paymentUtilities.enableFormElem("#braintree-payment-form");
                            } else if(resp.success) {
                                //paymentUtilities.hideShow($("#" + formId + " .blue-btn"));

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
