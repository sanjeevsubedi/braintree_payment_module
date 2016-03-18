<?php $this->lang->load("payment"); ?>
                                <!-- showing payment history data -->
                            <?php if(!empty($result)): ?>
                            <?php foreach ($result as $key => $res): ?>
                            <?php $payment_method = $res->paymentInstrumentType; ?>
                              <?php $pay_info = json_decode($res->info); ?> 
                                <tr>
                                    <td><?php echo date('d.m.Y',$res->createdAt); ?></td>
                                     <td><?php echo $res->id; ?></td>
                                    <!-- <td><a href="<?php echo form_base_url() ?>user/profile/transaction_detail/<?php echo $res->id; ?>"><?php echo $res->id; ?></a></td> -->
                                    <td><?php if($payment_method=='credit_card'){ ?>
                                     
                                      <img src="<?php echo $pay_info->imageUrl; ?>" alt="">
                                    <?php 
                                     echo $pay_info->bin.'******'.$pay_info->last4;  
                                    }else{ ?>
                                      <img src="<?php echo $pay_info->imageUrl; ?>" alt="">     
                                   <?php  
                                    echo $pay_info->payerEmail;
                                 } 

                                        ?></td>
                                
                                    <td> <?php echo CURRENCY; ?> <?php echo $res->amount; ?></td>
                                    <td><a target="_blank" href="<?php echo base_url() ?>invoices/physio_invoice<?php echo $res->id; ?>.pdf">Link for <?php echo $res->id; ?></a></td>
                                    <!-- <td><input type="checkbox" class="export_select" data-eid="<?php echo $key ?>"></td> -->
                                </tr>
                            <?php endforeach; ?>
                                   
                            <?php else: ?>
                                <?php echo lang("payment.no_data_found"); ?>
                            <?php endif; ?>
