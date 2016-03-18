<div id="paypal-container"></div>
                               
                                <script type="text/javascript">
                                //console.log(braintree);
                                braintree.setup('<?php echo $client_token;?>', "paypal", {
                                  container: "paypal-container",
                                  singleUse: false,
                                  //enableShippingAddress : true,
                                  onPaymentMethodReceived: function (obj) {

                                    paypalNonce = obj.nonce;
                                    $("#checkout").addClass("processPaypal");

                                    //paymentUtilities.processPayPal();
                                       //console.log(obj);
                                      /*var data = {
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
*/
                                      

                                  }
                                });
                                </script>
                                 