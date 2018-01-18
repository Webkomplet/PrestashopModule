$(function() {

    var apiKey = $('#MAILKOMPLET_API_KEY');
    var baseCrypt = $('#MAILKOMPLET_BASE_CRYPT');
    var modulePath = $('#MAILKOMPLET_MODULE_PATH');
    var submitButton = $('.panel-footer button[type="submit"]');
    var selectList = $('#MAILKOMPLET_LIST_ID');
    var selectListGroup = selectList.parents('.form-group');
    var strConnect = $('#MAILKOMPLET_STR_CONNECT');
    var strConnecting = $('#MAILKOMPLET_STR_CONNECTING');
    var strAjaxError = $('#MAILKOMPLET_STR_AJAX_ERROR');
    
    var connectButtonShown = false;

    // read mailing lists, if apiKey is empty?
    if (apiKey.val() == '') {
    	connectButtonShow();
    }
    
    // read mailing lists on api key change (and base crypt must be set)
    apiKey.change(function() {
    	if (baseCrypt.val()) {
    		connectButtonShow();
    	}
    });
    
    // read mailing lists on base crypt change (and api key must be set)
    baseCrypt.change(function () {
    	if (apiKey.val()) {
    		connectButtonShow();
    	}
    });
    
    // read mailing lists via api and fill appropriate select
    function connectButtonShow() {
    	if (!connectButtonShown) {
	        submitButton.hide();
	        selectListGroup.hide();
	
	        $('<div class="form-group"><label class="control-label col-lg-3"></label><div class="col-lg-9 "><input type="submit" value="' + strConnect.val() + '" id="connectButton" class=""></div></div>').insertAfter("#configuration_form .form-wrapper .form-group:nth-child(2)");
	        connectButtonShown = true;
    	}
    }
    
    $('#connectButton').click(function(e) {
        e.preventDefault();
        $(this).val(strConnecting.val() + '...');
        $.ajax({
                url: modulePath.val() + 'ajax.php',
                type: 'get',
                dataType: 'json',
                data: {
                	apiKey: apiKey.val(),
                	baseCrypt: baseCrypt.val(),
                },
                success: function(data) {
                	if ('data' in data) {
	                    selectList.html('');
	                    
	                    var selected = ' selected="selected"';
	                    $.each(data.data, function(key, val) {
	                        selectList.append('<option value="' + val.mailingListId + '"' + selected + '>' + val.name + '</option>');
	                        selected = '';
	                    });
	                    selectListGroup.show();
	                    submitButton.show();
	                    $('#connectButton').parents('.form-group').remove();
                	} else if ('message' in data) {
                		$('#connectButton').val(strConnect.val());
                		alert(strAjaxError.val());
                	}
                }
        });
        return false;
    });
});