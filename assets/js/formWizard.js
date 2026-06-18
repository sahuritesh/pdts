
function validateAndProcessDataForWizard(json) {
    var formId = json.formId;
    var url = json.url;
    var postKey = json.postKey;
    var primaryId = json.primaryId;
    var expectedState = json.expectedState;
    var currentState = json.currentstate;
    var type = json.type;
    var redirectTo = json.redirectTo;
    var callbackFunction = json.callbackFunction;

    if (validateFormdata(json)) {
        var form = $('#' + formId)[0];
        var data = new FormData(form);
        preloaderOverlay('show');
        ajaxRequestWithPromise(url, data, postKey, 1).then(function(response) {
            preloaderOverlay('hide');
            if (response.error == '0' || response.error == 0) {
                if (type == 'finish') {
                    $('.masterForm').trigger('reset');
                    $('.customWizard').addClass('disabled');
                    $('.masterId').val(0);
                    $('.customWizard-0').removeClass('disabled').addClass('current');
                    enableDisableSections(0);
                    var finishRedirect = redirectTo || $('#commonActionButton').attr('href') || '';
                    if (finishRedirect !== '') {
                        window.location.href = finishRedirect;
                    }
                } else {
                    var redirectUrl = response.redirect || response.redirect_url || '';
                    if (redirectUrl !== '') {
                        window.location.href = redirectUrl;
                        return;
                    }
                    var masterId = response.primaryId;
                    if (masterId !== '' && masterId !== null && masterId !== undefined) {
                        $('.masterId').val(masterId);
                    }
                    if (currentState == '0' || currentState === 0) {
                        $('#hdnForeignKeyId').val(masterId);
                    }
                    enableDisableSections(expectedState);
                    $('.customWizard-' + expectedState).removeClass('disabled').addClass('current');
                }
                if (callbackFunction !== '' && callbackFunction !== undefined) {
                    triggerCallBackFunction(callbackFunction);
                }
            } else {
                $('.customWizard-' + currentState).removeClass('disabled').addClass('current');
            }
        }).catch(function() {
            preloaderOverlay('hide');
        });
    } else {
        return false;
    }
}

function enableDisableSections(expectedState) {
    $('.commonSections').hide();
    $('.section' + expectedState).show();
    $('.customWizard').removeClass('current');
    $('.customWizard-' + expectedState).addClass('current');
}

function doInsertUpdate(formId, primaryId, expectedState, type, redirect, callbackFunction) {
    redirect = redirect || '';
    callbackFunction = callbackFunction || '';
    var targetUrl = $('#masterForm' + formId).attr('data-url');
    var json = {
        formId: 'masterForm' + formId,
        url: (typeof baseURL !== 'undefined' ? baseURL.replace(/\/$/, '') : '') + '/' + String(targetUrl || '').replace(/^\//, ''),
        postKey: targetUrl,
        primaryId: primaryId,
        currentstate: formId,
        expectedState: expectedState,
        type: type,
        redirectTo: redirect,
        callbackFunction: callbackFunction
    };
    validateAndProcessDataForWizard(json);
}

function calculateSteps(e, type, currentState, redirect, callbackFunction) {
    redirect = redirect || '';
    callbackFunction = callbackFunction || '';
    var primaryMasterId = $(e).closest('section').find('.masterId').attr('id');

    $('.customWizard').removeClass('current');
    if (type == 'previous') {
        if (currentState == '0' || currentState === 0) {
            enableDisableSections(0);
            $('.customWizard-0').addClass('current');
        } else {
            var prevState = parseInt(currentState, 10) - 1;
            enableDisableSections(prevState);
            $('.customWizard-' + prevState).removeClass('disabled').addClass('current');
        }
    } else if (type == 'next') {
        if (typeof clearSideLayout === 'function') {
            clearSideLayout();
        }
        if (currentState == 1 && typeof syncProjectDepartmentOrder === 'function') {
            syncProjectDepartmentOrder();
        }
        var nextState = parseInt(currentState, 10) + 1;
        doInsertUpdate(currentState, primaryMasterId, nextState, type, '', callbackFunction);
    } else if (type == 'finish') {
        doInsertUpdate(currentState, primaryMasterId, currentState, type, redirect, callbackFunction);
    }
}

function triggerCallBackFunction(functionName) {
    if (functionName == 'initProjectExecutionStep') {
        initProjectExecutionStep();
    } else if (functionName == 'getServiceResources') {
        getServiceResources();
    } else if (functionName == 'Gettreatmenttypes_pestcovered') {
        Gettreatmenttypes_pestcovered();
    } else if (functionName == 'getManpower') {
        getManpower();
    }
}
