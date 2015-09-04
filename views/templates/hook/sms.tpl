{*
* 2015 ekosshop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
*
*
*  @author ekosshop <info@ekosshop.com>
*  @shop http://ekosshop.com
*  @copyright  2015 ekosshop
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*}
<div class="col-lg-12">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-mobile-phone"></i> {l s='SMS Message'}
        </div>
        {if ($status==1)}
            <div id="errorSMS" class="alert alert-danger" style="display:none;"></div>
            <div id="okSMS" class="alert alert-success" style="display:none;"></div>
            <div id="sms_msgs" class="well hidden-print">
                <form id="smsForm">
                    <div id="sms_msg" class="form-horizontal">
                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='SMS Message'}</label>
                            <div class="col-lg-9">
                                <textarea id="txtsms_msg" class="textarea-autosize" name="sms"></textarea>
                                <p id="nbsmschars"></p>
                            </div>
                        </div>
                        <button type="submit" id="submitSMS" onclick="sendMsgtoclient(); return false;" class="btn btn-primary pull-right" name="submitSMS">
                            {l s='Send SMS message'}
                        </button>
                        <br/><br/>
                    </div>
                </form>
            </div>
        {else}
            <div id="sms_msg" class="form-horizontal">
                <div class="alert alert-danger">{$SMSerror}</div>
                <br/><br/>
            </div>
        {/if}
    </div>

    {if ($status==1)}
        <script type="text/javascript">
            $('#txtsms_msg').on('keyup', function(){
                var length = $('#txtsms_msg').val().length;
                if (length > 160) length = '160+';
                $('#nbsmschars').html(length+'/160');
            });
    
            function sendMsgtoclient() {
                $('#okSMS').hide();
                if($('#txtsms_msg').val() == '') {
                    $('#errorSMS').html("{l s='Error: SMS Message empty!'}");
                    $('#errorSMS').show();
                } else {
                    $('#errorSMS').hide();
                    $.ajax({
                        type:"POST",
                        url: "{$pathSMS}",
                        dataType: "json",
                        data : {
                            ajax: true,
                            action: "Sendekosms",
                            dataField: {
                                id_customer: {$id_customer},
                                id_order: {$id_order},
                                smsmsg: $('#txtsms_msg').val()
                            },
                        },
                        success : function(jsonData) {
                            if(jsonData.status == 'ok') {
                                $('#okSMS').html("{l s='SMS Message Sent to Customer'}");
                                $('#okSMS').show();
                            } else {
                                $('#errorSMS').html(jsonData.error);
                                $('#errorSMS').show();
                            }
                        }
                    });
                }
            }
        </script>
    {/if}
</div>