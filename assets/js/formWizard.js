
function validateAndProcessDataForWizard(json) {
    var formId = json.formId;
    var url = json.url;
    var postKey = json.postKey;
    var primaryId = json.primaryId; 
    var expectedState = json.expectedState;
    var currentState = json.currentstate;
    var type = json.type;    
    var redirectTo =  json.redirectTo;    
    var callbackFunction = json.callbackFunction;
    if (validateFormdata(json)) {
        var form = $('#' + formId)[0];
        var data = new FormData(form);
        preloaderOverlay('show');
        ajaxRequestWithPromise(url, data, postKey, 1).then(function(response) {
            isResolved = true;
            preloaderOverlay('hide');
            if (response.error == '0') {
                if (type == 'finish') {
                    $('.masterForm').trigger("reset");                  
                    $('.masterId').val(0);
                    $('.customWizard-0 a').removeClass('disabled').addClass('active');
                    enableDisableSections(0);
                    if(redirectTo!=''){
                        setTimeout(function(){
                            window.location.href = redirectTo;
                        },2000); 
                    }         
                } else {
                    var masterId = response.primaryId;
                    if (masterId != '') {
                        $('.masterId').val(masterId);
                    }                    
                    enableDisableSections(expectedState);                  
                }
                if(callbackFunction!=''){
                   
                    triggerCallBackFunction(callbackFunction);
                } 
            } else {
                enableDisableSections(currentState);                
            }
            console.log('p is resolved?', isResolved);
        }).catch(function(err) {           
            preloaderOverlay('hide');           
        })
    } else {
        return false;
    }
    
}

function enableDisableSections(expectedState) {     
    $('.customWizard a').removeClass('active');    
    $('.customWizard-'+expectedState+' a').removeClass('disabled').addClass('active');
    $('.tab-custom1').removeClass('active');
    $('.tab-pane').removeClass('active');    
    $('#genericTab'+expectedState).addClass('active');    
}

enableDisableSections(0);

function doInsertUpdate(formId, primaryId, expectedState, type,redirect='',callbackFunction='') {
    var targetUrl = $('#masterForm'+formId).attr('data-url');
    var json = {
        "formId": "masterForm" + formId,  
        "url": baseURL + targetUrl,
        "postKey": targetUrl,
        "primaryId": primaryId,
        "currentstate": formId,
        "expectedState": expectedState,
        "type": type,
        "redirectTo": redirect,
        "callbackFunction":callbackFunction
    }
    validateAndProcessDataForWizard(json);
}

function calculateSteps(e, type, currentState,redirect='',callbackFunction='') {
    var primaryMasterId = $(e).closest('.tab-content').find('.masterId').attr('id');
    $('.customWizard').removeClass('current');
    if (type == 'previous') {
        if (currentState == '0') {           
            enableDisableSections(currentState);
        } else {
            expectedState = currentState - 1;
            enableDisableSections(expectedState);
        }
    } else if (type == 'next') {
        clearSideLayout();
        expectedState = currentState + 1;
        var response = doInsertUpdate(currentState, primaryMasterId, expectedState, type,'',callbackFunction);

    } else if (type == 'finish') {
        expectedState = currentState;
        var response = doInsertUpdate(currentState, primaryMasterId, expectedState, type,redirect,callbackFunction);
    }
}

function triggerCallBackFunction(functionName){
    if(functionName == 'getServiceResources'){
        getServiceResources();
    }else if(functionName == 'Gettreatmenttypes_pestcovered'){
        Gettreatmenttypes_pestcovered();
    }else if(functionName == 'getManpower'){
        getManpower();
    }
}