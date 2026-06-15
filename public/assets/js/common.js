$.ajaxSetup({
    headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
    },
});

// Reset button states on page load and browser back navigation
function resetPageButtonStates() {
    if (typeof resetAllButtonStates === 'function') {
        resetAllButtonStates();
    }
}

$(document).ready(function () {
    // Reset all button states on page load (handles browser back button)
    resetPageButtonStates();
    
    $(".multipleSelectDropdown").select2({
        placeholder: "Please Select",
    });
    $(".multipleSerachBox").select2({
        placeholder: "Please Select",
    });
    $(".multipleSearchBox").select2({
        placeholder: "Please Select Trainee",
    });
    $(".numberOnly").keypress(function (e) {
        if (
            e.which != 8 &&
            e.which != 0 &&
            e.which != 110 &&
            e.which != 46 &&
            (e.which < 48 || e.which > 57)
        ) {
            $(this).attr("placeholder", "Allows Numbers Only");
            return false;
        }
    });
    $(document).on("keypress",".numberOnly",function (e) {
        if (
            e.which != 8 &&
            e.which != 0 &&
            e.which != 110 &&
            e.which != 46 &&
            (e.which < 48 || e.which > 57)
        ) {
            $(this).attr("placeholder", "Allows Numbers Only");
            return false;
        }
    });
    $(".nameOnly").keypress(function (e) {
        var regex = new RegExp("^[a-zA-Z ]+$");
        var strigChar = String.fromCharCode(!e.charCode ? e.which : e.charCode);
        var text = $(this).val();

        if (text.length == 0 && strigChar.indexOf(" ") == 0) {
            return false;
        }
        if (text.length > 0 && text.includes(" ")) {
            e.target.value = text.replace(/ +/g, " ");
        }
        if (regex.test(strigChar)) {
            return true;
        }
        return false;
    });
    $(".namenumberOnly").keypress(function (e) {
        var regex = new RegExp("^[a-zA-Z0-9 .]+$");
        var strigChar = String.fromCharCode(!e.charCode ? e.which : e.charCode);
        var text = $(this).val();

        if (text.length == 0 && strigChar.indexOf(" ") == 0) {
            return false;
        }
        if (text.length > 0 && text.includes(" ")) {
            e.target.value = text.replace(/ +/g, " ");
        }
        if (regex.test(strigChar)) {
            return true;
        }
        return false;
    });
    $(".decimalNumberOnly").keypress(function (e) {
        var regex = new RegExp("^[.0-9 ]+$");
        var strigChar = String.fromCharCode(!e.charCode ? e.which : e.charCode);
        var text = $(this).val();

        if (text.length == 0 && strigChar.indexOf(" ") == 0) {
            return false;
        }
        if (text.length > 0 && text.includes(" ")) {
            e.target.value = text.replace(/ +/g, " ");
        }
        if (regex.test(strigChar)) {
            return true;
        }
        return false;
    });
    $(".singlespace").keypress(function (e) {
        var inputText = $(this).val();
        if (e.which === 32) {
            // Check if the input text already ends with a space
            if (inputText.endsWith(" ")) {
                e.preventDefault(); // Prevent adding additional spaces
            }
        }
    });

    $(".email").blur(function (e) {
        var inputVal = $(this).val();
        $("span.error-keyup-7").remove();
        var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
        if (!emailReg.test(inputVal)) {
            $(this).focus();
            $(this).after(
                '<span class="error error-keyup-7">Invalid Email Format.</span>'
            );
        }
    });

    $(".nospaces").on({
        keydown: function (e) {
            if (e.which === 32) return false;
        },
        change: function () {
            this.value = this.value.replace(/\s/g, "");
        },
    });

    $(".domainOnly").keypress(function (e) {
        var regex = new RegExp("^[./a-zA-Z]*$");
        var strigChar = String.fromCharCode(!e.charCode ? e.which : e.charCode);
        var text = $(this).val();

        if (text.length == 0 && strigChar.indexOf(" ") == 0) {
            return false;
        }
        if (text.length > 0 && text.includes(" ")) {
            e.target.value = text.replace(/ +/g, " ");
        }
        if (regex.test(strigChar)) {
            return true;
        }
        return false;
    });

    $(".pastDatesHide").datepicker({
        todayBtn: 1,
        autoclose: true,
        startDate: new Date(),
        format: "yyyy-mm-dd",
    });    

});

function initializeDaterangepicker() {
    let mindate = new Date(new Date().getFullYear(), 0, 1);
    let maxdate = new Date(new Date().getFullYear(), 11, 31);
    let startdata = moment().startOf('month');
    let enddate =  moment().endOf('month');
    if($('input[name="daterange"]').hasClass('default'))
    {
        defaultDateSelected = new Date();
        startdata = moment();
        enddate = moment();
    }
    $('input[name="daterange"]').daterangepicker({
        opens: 'left',
        minDate: mindate,
        maxDate: maxdate,
        startDate: startdata,
        endDate:enddate,
    }, function(start, end, label) {
        //console.log(start + '' + end + '' + label);
    });
}


$("#telephone").on("keypress", function (e) {
    var inputValue = e.key;
    var currentValue = $(this).val();
    // Allow digits and one hyphen
    if (
        /^\d+$/.test(inputValue) ||
        (inputValue === "-" && currentValue.indexOf("-") === -1)
    ) {
        return true;
    } else {
        e.preventDefault();
        return false;
    }
});

/*$(".reset_cls").click(function (e) {
    var form_id = this.form.id;
    console.log
    $('#' + form_id).find('input, select, textarea').val('');
    $('#' + form_id).find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
});*/


document.addEventListener('DOMContentLoaded', function () {
    /*
    var table = $("#ucList").DataTable({
        paging: false,  // Disable pagination
        stateSave: true
    });
    */
    document.querySelectorAll(".reset_cls").forEach(function (button) {
        button.addEventListener('click', function (e) {
            var form_id = this.form.id;
            var form = document.getElementById(form_id);
            var form_url = form.action;
            const pageKey = 'ucFilterForm_' + window.location.pathname;
            localStorage.removeItem(pageKey);
            $('#searchform')[0].reset();
            $('#searchform').find('select').val('').trigger('change');
            form.querySelectorAll('input, textarea').forEach(function (element) {
                if (element.type === 'checkbox' || element.type === 'radio') {
                    element.checked = false;
                } else {
                    element.value = '';
                }
            });

            form.querySelectorAll('select').forEach(function (element) {
                if ($(element).hasClass('select2-hidden-accessible')) {
                    $(element).val(null).trigger('change');
                } else {
                    element.selectedIndex = 0;
                }
            });              

               
           
        });
    });
});


function parseFormErrors(res, msg_type) {
    let result = "";
    
    // Check if msg is a string (HTML format like "<li>Error</li>")
    if (typeof res.msg === 'string') {
        // Remove all HTML tags and get plain text
        result = res.msg.replace(/<[^>]*>/g, '');
    } else if (Array.isArray(res.msg)) {
        // If msg is an array
        for (let i = 0; i < res.msg.length; i++) {
            let msgText = res.msg[i].toString().replace(/<[^>]*>/g, '');
            result += msgText + (i < res.msg.length - 1 ? '<br>' : '');
        }
    } else if (typeof res.msg === 'object' && res.msg !== null) {
        // If msg is an object
        let keys = Object.keys(res.msg);
        if (keys.length > 0) {
            for (let i = 0; i < keys.length; i++) {
                let msgText = res.msg[keys[i]].toString().replace(/<[^>]*>/g, '');
                result += msgText + (i < keys.length - 1 ? '<br>' : '');
            }
        }
    } else {
        result = (res.msg || '').toString().replace(/<[^>]*>/g, '');
    }
    
    toastr.options = {
        closeButton: true,
        progressBar: true,
    };
    if (msg_type == "error") {
        toastr.error(result);
    } else if (msg_type == "success") {
        toastr.success(result);
    }
}
//
// function changeMasterDataStatus(
//     rowid,
//     rowstatus,
//     tableName,
//     targetTable,
//     col = ""
// ) {
//     let id = rowid;
//     let status = rowstatus;
//     let table = tableName;
//     let msg = "";
//     let colm = col;
//     if (status == 1) {
//         status = 0;
//         msg = "Status De-Activated successfully!!";
//     } else {
//         status = 1;
//         msg = "Status Activated successfully!!";
//     }
//     $(this).val(status);
//     let input = {
//         id: id,
//         status: status,
//         table: table,
//         colm: colm,
//         _token: $('meta[name="csrf-token"]').attr("content"),
//     };
//     $.ajax({
//         type: "POST",
//         data: input,
//         url: baseURL + "changeMasterDataStatus",
//         success: function (response) {
//             toastr.options = {
//                 closeButton: true,
//                 progressBar: true,
//             };
//             toastr.success(msg);
//             ajaxDataTableReload(targetTable);
//         },
//     });
// }

function ajaxRequestPromise(url, parameterData) {
    // Use unified loader
    showGlobalLoader(true);
    // Try to get CSRF token from hidden inputs first, then fallback to meta tag
    var tokenName = $("#csrfTokenName").val() || "_token";
    var tokenValue = $("#csrfTokenValue").val() || $('meta[name="csrf-token"]').attr('content') || '';
    return new Promise(function (resolve, reject) {
        var form_data = new FormData();
        var jsn = JSON.stringify(parameterData);
        form_data.append("data", jsn);
        if (tokenValue) {
            form_data.append(tokenName, tokenValue);
        }
        $.ajax({
            url: url,
            type: "POST",
            enctype: "multipart/form-data",
            processData: false, // Important!
            contentType: false,
            cache: false,
            data: form_data,
            success: function (res) {
                // Hide unified loader
                showGlobalLoader(false);
                // Check if data is already an object or needs parsing
                var parsedRes = res;
                if (typeof res === 'string') {
                    try {
                        parsedRes = JSON.parse(res);
                    } catch (e) {
                        // If parsing fails, resolve with original string
                        resolve(res);
                        return;
                    }
                }
                // Note: displayResponseMessage is NOT called here automatically
                // Call it manually only for operations (add, update, delete, etc.), not for data fetching
                resolve(parsedRes);
            },
            error: function (e) {
                // Hide unified loader
                showGlobalLoader(false);
                reject(e);
            },
        });
    });
}

function ajaxDataTableReload(targetTable) {
    $("#" + targetTable).DataTable().ajax.reload(null, false);
}


function uploadFile(formdata, targetId, hiddenele) {
    var file = formdata.get("file");
    var fileName = file.name;

    if (formdata.get('filetype') && formdata.get('filetype') != '' && formdata.get('filetype') != 'undefined') {
        var filetype = formdata.get('filetype');
        if (filetype == 'image') {
            var method = 'ImageUpload';
        } else {
            var method = 'docUpload';
        }
    } else {
        var fileExtension = fileName.split('.').pop();
        var fileExtensions = ['png', 'jpg', 'jpeg', 'svg', 'PNG', 'JPEG', 'JPG'];
        var method = 'ImageUpload';
        if ($.inArray(fileExtension, fileExtensions) === -1) {
            method = 'docUpload';
        }
    }
    preloaderOverlay("show");
    $("#" + targetId).next().find(".progress-bar").css("width", "0%");
    $("#" + targetId).next().find(".progress").show();
    $.ajax({
        url: baseURL + method,
        type: "POST",
        data: formdata,
        contentType: false,
        async: true,
        cache: false,
        processData: false,
        xhr: function () {
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener(
                "progress",
                function (evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total;
                        percentComplete = parseInt(percentComplete * 100);
                        console.log(percentComplete);
                        $("#" + targetId)
                            .next()
                            .find(".progress-bar")
                            .text(percentComplete + "%");
                        $("#" + targetId)
                            .next()
                            .find(".progress-bar")
                            .css("width", percentComplete + "%");
                        if (percentComplete == "100") {
                        }
                    }
                },
                false
            );
            return xhr;
        },
        success: function (data) {
            preloaderOverlay("hide");
            var data = JSON.parse(data);
            if (data.error == 0) {
                $("#" + targetId + "").html(data.uploadedFile);
                setTimeout(function () {
                    var uploadedfile = $("#" + targetId + " a").attr(
                        "finalfile"
                    );
                    $("#" + hiddenele + "").val(uploadedfile);
                    $("#" + targetId)
                        .next()
                        .hide();
                    $("#" + targetId)
                        .next()
                        .find(".progress-bar")
                        .css("width", "0%");
                }, 1000);
            } else {
                $("#" + targetId)
                    .next()
                    .hide();
                $("#" + targetId)
                    .next()
                    .find(".progress-bar")
                    .css("width", "0%");
                $("#" + targetId + "").html(data.msg);
                $("#" + targetId + "").css("color", "red");
                if (data.error == 1) {
                    $(this).val(null);
                    //toastr.error(data.msg);
                }
            }
        },
        error: function () { },
    });
}

$("#checkAll").on("change", function (e) {
    var chval = $(this).is(":checked");
    $(".singleCheckBox").prop("checked", chval);
});
$(document).delegate(".singleCheckBox", "click", function (e) {
    if ($(".singleCheckBox:checked").length == $(".singleCheckBox").length) {
        $("#checkAll").prop("checked", true);
    } else {
        $("#checkAll").prop("checked", false);
    }
});

function updateMenuTitle(url, rowid, title, mesha, valCol) {
    let data = {
        id: rowid,
        title: title,
        mesha: mesha,
        valCol: valCol,
    };
    response = ajaxRequestPromise(url, data);
    response.then(function (v) {
        var res = JSON.parse(v);
        toastr.options = {
            closeButton: true,
            progressBar: true,
        };
        toastr.success("Updated successfully!!");
    });
}
function showDescription(id, required, mesha) {
    let url = baseURL + "showDescription";
    let data = {
        id: id,
        mesha: mesha,
        required: required,
    };
    response = ajaxRequestPromise(url, data);
    response.then(function (v) {
        var res = JSON.parse(v);
        $(".displayDiscription").html(res);
    });
}

$("#start_date")
    .datepicker({
        todayBtn: 1,
        autoclose: true,
        startDate: new Date(),
        format: "yyyy-mm-dd",
    })
    .on("changeDate", function (selected) {
        var minDate = new Date(selected.date.valueOf());
        $("#end_date").val("");
        $("#end_date").datepicker("setStartDate", minDate);
    });
$("#end_date").datepicker({
    todayBtn: 1,
    autoclose: true,
    format: "yyyy-mm-dd",
});

function updateSortOrder(tableId, tableBodyClass, checkData) {
    $("#" + tableId + " tbody")
        .sortable({
            helper: "clone",
            axis: "y",
            update: function (event, ui) {
                selectedCustomerOrder = [];
                parentIdToStoreOrder = [];
                SortedOrderAfterSorting = [];
                $("." + tableBodyClass + " tr").each(function () {
                    selectedCustomerOrder.push($(this).attr("current_sort_Id"));
                    parentIdToStoreOrder.push($(this).attr("data-id"));
                });
                selectedCustomerOrderLength = selectedCustomerOrder.length;
                var ord = 1;
                for (
                    var order = 0;
                    order < selectedCustomerOrderLength;
                    order++
                ) {
                    SortedOrderAfterSorting.push(ord);
                    ord++;
                }
                var url = baseURL + "updateSortOrder";
                var data = {
                    parentIdToStoreOrder: parentIdToStoreOrder,
                    sortedOrder: selectedCustomerOrder,
                    customSortingOrder: SortedOrderAfterSorting,
                    checkData: checkData,
                };
                response = ajaxRequestPromise(url, data);
                response.then(
                    function (v) {
                        var res = JSON.parse(v);
                        if (res.error == 0) {
                            parseFormErrors(res, "success");
                        } else if (res.error == 2) {
                            parseFormErrors(res, "error");
                        } else {
                            parseFormErrors(res, "error");
                        }
                    },
                    function (e) {
                        // console.log(e);
                    }
                );
            },
        })
        .disableSelection();
}

function validateFormdata(json, isFormData = "") {
    toastr.options = {
        closeButton: true,
        progressBar: true,
    };
    // DO NOT show loader during validation - only show when actually submitting
    var formId = json.formId;
    var url = json.url;
    var postKey = json.postKey;
    var primaryId = json.primaryId;
    removeErrorClass();
    var doSubmit = true;
    var msg = "";
    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/; //email validation
    //password validation
    var number = /([0-9])/;
    var uppercase = /([A-Z])/;
    var lowercase = /([a-z])/;
    var special_characters = /([~,!,@,#,$,%,^,&,*,-,_,+,=,?,>,<])/;
    $("#" + formId + " input,#" + formId + " select,#" + formId + " textarea,#" + formId + " radio").each(function (index) {
        var input = $(this);
        var name = input.attr("name");

        if (typeof name !== "undefined" && name !== false) {
            var id = input.attr("id");
            var labeltext = "";
            var name1 = name.replace(/_/g, " ");
            name1 = name1.toLowerCase().replace(/\b[a-z]/g, function (letter) {
                return letter.toUpperCase();
            });
            var labeltext = $(this).attr("data-msg");
            if (labeltext == undefined) {
                labeltext = name1;
            }
            if (input.hasClass("required")) {
                if (input.val() == "") {
                    msg = "The Field " + labeltext + " is required";
                    // showValidationMsg(msg, id);
                    toastr.error("<li>" + msg + "</li>");
                    highlightErrorInput(input);
                    doSubmit = false;
                    return false;
                }
            }

            if (input.hasClass("emailOnly")) {
                if (!input.val().match(emailReg)) {
                    msg = "The Field " + labeltext + " is not a valid Email ID";
                    toastr.error(msg);
                    highlightErrorInput(input);
                    doSubmit = false;
                    return false;
                }
            }

            // Mobile number validation (10 digits)
            if (input.hasClass("mobileOnly")) {
                var mobileValue = input.val().replace(/\D/g, ''); // Remove non-digits
                // Only validate if field has value (for optional fields)
                if (mobileValue && mobileValue.length !== 10) {
                    msg = "The Field " + labeltext + " must be exactly 10 digits";
                    toastr.error(msg);
                    highlightErrorInput(input);
                    doSubmit = false;
                    return false;
                }
            }

            // Aadhaar number validation (12 digits)
            if (input.hasClass("aadhaarOnly")) {
                var aadhaarValue = input.val().replace(/\D/g, ''); // Remove non-digits
                if (aadhaarValue.length !== 12) {
                    msg = "The Field " + labeltext + " must be exactly 12 digits";
                    toastr.error(msg);
                    highlightErrorInput(input);
                    doSubmit = false;
                    return false;
                }
            }

            // PIN code validation (6 digits)
            if (input.hasClass("pincodeOnly")) {
                var pincodeValue = input.val().replace(/\D/g, ''); // Remove non-digits
                if (pincodeValue.length !== 6) {
                    msg = "The Field " + labeltext + " must be exactly 6 digits";
                    toastr.error(msg);
                    highlightErrorInput(input);
                    doSubmit = false;
                    return false;
                }
            }

            // Number only validation (must contain only numbers)
            if (input.hasClass("numberOnly")) {
                var numberValue = input.val();
                if (numberValue && numberValue.trim() !== "" && !/^\d+$/.test(numberValue)) {
                    msg = "The Field " + labeltext + " must contain only numbers";
                    toastr.error(msg);
                    highlightErrorInput(input);
                    doSubmit = false;
                    return false;
                }
            }

            // File upload validation
            if (input.hasClass("fileOnly") && input.attr("type") === "file") {
                var fileInput = input[0];
                var isMultiple = input.attr("multiple") !== undefined;
                if (isMultiple) {
                    if (fileInput.files.length === 0) {
                        msg = "The Field " + labeltext + " requires at least one file to be uploaded";
                        toastr.error(msg);
                        highlightErrorInput(input);
                        doSubmit = false;
                        return false;
                    }
                } else {
                    if (fileInput.files.length === 0) {
                        msg = "The Field " + labeltext + " requires a file to be uploaded";
                        toastr.error(msg);
                        highlightErrorInput(input);
                        doSubmit = false;
                        return false;
                    }
                }
            }

            // Range validation (min/max)
            if (input.hasClass("rangeOnly")) {
                var value = parseFloat(input.val());
                var min = parseFloat(input.attr("data-min") || input.attr("min") || 0);
                var max = parseFloat(input.attr("data-max") || input.attr("max") || Infinity);
                
                if (input.val() && input.val().trim() !== "" && !isNaN(value)) {
                    if (value < min || value > max) {
                        msg = "The Field " + labeltext + " must be between " + min + " and " + max;
                        toastr.error(msg);
                        highlightErrorInput(input);
                        doSubmit = false;
                        return false;
                    }
                }
            }

            // Date validation
            if (input.hasClass("dateOnly") && input.attr("type") === "date") {
                var dateValue = input.val();
                if (dateValue) {
                    var selectedDate = new Date(dateValue);
                    var maxDate = input.attr("data-max-date") || input.attr("max");
                    if (maxDate) {
                        var maxDateObj = new Date(maxDate);
                        if (selectedDate > maxDateObj) {
                            msg = "The Field " + labeltext + " cannot be after " + maxDateObj.toLocaleDateString();
                            toastr.error(msg);
                            highlightErrorInput(input);
                            doSubmit = false;
                            return false;
                        }
                    }
                }
            }

            // Checkbox validation
            if (input.attr("type") === "checkbox" && input.hasClass("required")) {
                if (!input.is(":checked")) {
                    msg = "The Field " + labeltext + " must be checked";
                    toastr.error(msg);
                    highlightErrorInput(input);
                    doSubmit = false;
                    return false;
                }
            }

            if (input.hasClass("password")) {
                if (input.val().length < 8) {
                    msg =
                        "The Field " +
                        labeltext +
                        " Should Have Minimum 8 Characters.";
                    toastr.error(msg);
                    highlightErrorInput(input);
                    doSubmit = false;
                    return false;
                } else {
                    if (!input.val().match(number)) {
                        msg =
                            "The Field " +
                            labeltext +
                            " Should Have at least One Number";
                        toastr.error(msg);
                        highlightErrorInput(input);
                        doSubmit = false;
                        return false;
                    } else if (!input.val().match(uppercase)) {
                        msg =
                            "The Field " +
                            labeltext +
                            " Should Have at least One Uppercase Letter";
                        toastr.error(msg);
                        highlightErrorInput(input);
                        doSubmit = false;
                        return false;
                    } else if (!input.val().match(lowercase)) {
                        msg =
                            "The Field " +
                            labeltext +
                            " Should Have at least One Lowercase Letter";
                        toastr.error(msg);
                        highlightErrorInput(input);
                        doSubmit = false;
                        return false;
                    } else if (!input.val().match(special_characters)) {
                        msg =
                            "The Field " +
                            labeltext +
                            " Should Have at least One Special Chatecter";
                        toastr.error(msg);
                        highlightErrorInput(input);
                        doSubmit = false;
                        return false;
                    }
                }
            }
        }
    });
    if (!doSubmit) {
        // Validation failed - no loader was shown, so no need to hide
        return false;
    } else {
        return true;
    }
}

function highlightErrorInput(input) {
    input.addClass('errorBorder').focus();
}

function validateAndProcessData(json, isFormData = "") {
    toastr.options = {
        closeButton: true,
        progressBar: true,
    };
    // Use unified loader utility
    showGlobalLoader(true);
    var formId = json.formId;
    var url = json.url;
    var postKey = json.postKey;
    var primaryId = json.primaryId;
    removeErrorClass();
    if (validateFormdata(json)) {
        tinyMCE.triggerSave();
        
        // Find submit button for loading state
        var $submitButton = findSubmitButton('#' + formId);
        if ($submitButton && $submitButton.length > 0) {
            setButtonLoadingState($submitButton, true);
        }
        
        if (isFormData) {
            var form = $("#" + formId)[0];
            var data = new FormData(form);
        } else {
            data = $("#" + formId).serialize();
        }
        setTimeout(function() {
            ajaxRequestWithPromise(url, data, postKey, isFormData, '', $submitButton ? $submitButton : null)
                .then(function (response) {
                    // Use unified loader utility
                    showGlobalLoader(false);
                    if (response.error == "0" || response.error == 0) {
                        if(primaryId !=undefined || primaryId!=null){
                            var regId = $("#" + primaryId).val();
                        }else{
                            var regId = 0;
                        }
                        if (regId == undefined || regId == 0) {
                            $("#" + formId).trigger("reset");
                        }
                    return true;
                    } else {
                        doSubmit = false;
                        return false;
                    }
                })
                .catch(function (err) {
                    console.log(err);
                    // Use unified loader utility
                    showGlobalLoader(false);
                    return false;
                });
        },2000);
    } else {
        return false;
    }
}

function changeMasterDataStatus(
    rowid,
    rowstatus,
    tableName,
    targetTable,
    col = ""
) {
    $.alert({        
        title: "Confirmation",
        content: " Are you sure to change the status? ",
        rtl: true,
        closeIcon: true,
        buttons: {
            confirm: {
                text: "Confirm ",
                btnClass: "btn-blue",
                action: function () {
                    let id = rowid;
                    let status = rowstatus;
                    let table = tableName;
                    let msg = "";
                    if (status == 3) {
                        status = 4;     
                    } else if(status == 4){
                        status = 3;
                    }else{
                        status = status;
                    }
                    $(this).val(status);
                    input = {
                        id: id,
                        status: status,
                        table: table,
                        token: $('meta[name="csrf-token"]').attr("content"),
                    };
                    // Note: ajaxRequestWithPromise will automatically show toastr messages
                    ajaxRequestWithPromise(baseURL + "changestatus", input, 'change_status', 0).then(function(response) {
                        var res = typeof response === 'string' ? JSON.parse(response) : response;
                        ajaxDataTableReload(targetTable);
                        // Toastr message is already shown by ajaxRequestWithPromise via displayResponseMessage
                    }).catch(function(error) {
                        // Error handling is done automatically by ajaxRequestWithPromise
                    });
                },
            },
            cancel: {
                text: "Cancel ",
                action: function () { },
            },
        },
    });
}

$(".lettersonly").keydown(function (e) {
    if (e.shiftKey || e.ctrlKey || e.altKey) {
        e.preventDefault();
    } else {
        var key = e.keyCode;
        if (
            !(
                key == 8 ||
                key == 32 ||
                key == 46 ||
                (key >= 35 && key <= 40) ||
                (key >= 65 && key <= 90)
            )
        ) {
            $(this).attr("placeholder", "Allows Alphabets only");
            e.preventDefault();
        }
    }
});

// for decimal input
$(document).on('input', '.decimalInput', function() {
    let value = $(this).val();
    // Allow only numbers and one decimal point
    value = value.replace(/[^0-9.]/g, '');
    // Prevent multiple decimal points
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts[1];  // Ignore further decimal points
    }

    // Limit to two decimal places
    if (parts.length > 1 && parts[1].length > 2) {
        value = parts[0] + '.' + parts[1].substring(0, 2);
    }

    // Set the restricted value back to the input field
    $(this).val(value);
});

// Initialize TinyMCE for all textareas with .tinymceEditor class
// Flag-based approach:
// - data-inline-styles="true" or "yes" or "1" = Use OLD approach (direct init with inline styles)
// - data-inline-styles="false" or "no" or "0" or not set = Use utility-based approach (CSS classes)
$(document).ready(function() {
    console.log('[Common.js] Initializing TinyMCE editors...');
    console.log('[Common.js] Found textareas with tinymceEditor class:', $('textarea.tinymceEditor').length);
    
    $('textarea.tinymceEditor').each(function() {
        var $textarea = $(this);
        var textareaId = $textarea.attr('id');
        var selector = textareaId ? '#' + textareaId : null;
        
        console.log('[Common.js] Processing textarea:', textareaId, 'selector:', selector);
        
        if (!selector) {
            // If no ID, skip or use a generated selector
            console.log('[Common.js] Skipping textarea without ID');
            return;
        }
        
        // Skip CMS page content editors - they are handled by pages.js
        if (textareaId === 'content_en' || textareaId === 'content_hi') {
            console.log('[Common.js] Skipping CMS page content editor (handled by pages.js):', textareaId);
            return;
        }
        
        // Check for inline styles flag
        var inlineStylesAttr = $textarea.attr('data-inline-styles');
        var useInlineStyles = inlineStylesAttr === 'true' || inlineStylesAttr === '1' || inlineStylesAttr === 'yes';
        
        if (useInlineStyles) {
            // OLD APPROACH: Direct TinyMCE initialization with inline styles (for email templates)
            tinymce.init({
                selector: selector,
                menubar: false,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount', 'fontselect'
                ],
                toolbar:
                    "undo redo | formatselect | fontselect fontsizeselect |" +
                    "bold italic | alignleft aligncenter " +
                    "alignright alignjustify | bullist numlist outdent indent | code",
                content_style: `
                    @import url('https://fonts.googleapis.com/css2?family=Ovo&display=swap');
                    @import url('https://fonts.googleapis.com/css2?family=Calibri&display=swap');
                    @import url('https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap');
                    body { font-family:Helvetica,Arial,sans-serif; font-size:14px }
                    .my-custom-font {
                        font-family: 'Ovo';
                    },
                    @font-face {
                        font-family: 'Calibri';
                        src: local('Calibri'), url('assets/fonts/calibri-regular.ttf') format('ttf');
                    },
                    .calibri { font-family: 'Calibri', sans-serif; }`,
                font_formats: 'Arial=arial,helvetica,sans-serif; Ovo=Ovo,sans-serif; Calibri=Calibri,sans-serif; Great Vibes=Great Vibes,cursive;',
                // Inline styles for email client compatibility
                inline_styles: true,
                paste_remove_styles: false,
                paste_retain_style_properties: 'all'
            });
        } else {
            // NEW APPROACH: Use utility function (CSS classes)
            if (typeof TinyMCEUtils !== 'undefined') {
                // Check if this is a content editor (section_content_en or section_content_hi)
                var isContentEditor = textareaId === 'section_content_en' || textareaId === 'section_content_hi';
                var customOptions = {
                    content_style: `
                        @import url('https://fonts.googleapis.com/css2?family=Ovo&display=swap');
                        @import url('https://fonts.googleapis.com/css2?family=Calibri&display=swap');
                        @import url('https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap');
                        body { font-family:Helvetica,Arial,sans-serif; font-size:14px }
                        .my-custom-font {
                            font-family: 'Ovo';
                        },
                        @font-face {
                            font-family: 'Calibri';
                            src: local('Calibri'), url('assets/fonts/calibri-regular.ttf') format('ttf');
                        },
                        .calibri { font-family: 'Calibri', sans-serif; }`
                };
                
                // Increase height for content editors
                if (isContentEditor) {
                    customOptions.height = 500;
                }
                
                TinyMCEUtils.init(selector, customOptions);
            } else {
                // Fallback if utility not loaded
                tinymce.init({
                    selector: selector,
                    menubar: false,
                    plugins: [
                        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                        'insertdatetime', 'media', 'table', 'help', 'wordcount', 'fontselect'
                    ],
                    toolbar:
                        "undo redo | formatselect | fontselect fontsizeselect |" +
                        "bold italic | alignleft aligncenter " +
                        "alignright alignjustify | bullist numlist outdent indent | code",
                    content_style: `
                        @import url('https://fonts.googleapis.com/css2?family=Ovo&display=swap');
                        @import url('https://fonts.googleapis.com/css2?family=Calibri&display=swap');
                        @import url('https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap');
                        body { font-family:Helvetica,Arial,sans-serif; font-size:14px }
                        .my-custom-font {
                            font-family: 'Ovo';
                        },
                        @font-face {
                            font-family: 'Calibri';
                            src: local('Calibri'), url('assets/fonts/calibri-regular.ttf') format('ttf');
                        },
                        .calibri { font-family: 'Calibri', sans-serif; }`,
                    font_formats: 'Arial=arial,helvetica,sans-serif; Ovo=Ovo,sans-serif; Calibri=Calibri,sans-serif; Great Vibes=Great Vibes,cursive;',
                });
            }
        }
    });
});

function stringGen(len) {
    var text = "";
    var charset = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < len; i++)
        text += charset.charAt(Math.floor(Math.random() * charset.length));
    return text;
}
function unlinktheFile(rowid, tableName) {
    $.alert({
        title: "Confirmation",
        content: " Are you sure, you want to delete the file? ",
        rtl: true,
        closeIcon: true,
        buttons: {
            confirm: {
                text: "Confirm ",
                btnClass: "btn-blue",
                action: function () {
                    let id = rowid;
                    let table = tableName;
                    let msg = "";
                    let input = {
                        id: id,
                        table: table,
                        token: $('meta[name="csrf-token"]').attr("content"),
                    };
                    // Note: ajaxRequestWithPromise will automatically show toastr messages
                    ajaxRequestWithPromise(baseURL + "unlinktheFile", input, 'unlink_file', 0).then(function(response) {
                        if (response && (response.error == 0 || response.error == "0")) {
                            $(this).parent("span").remove();
                            setTimeout(function () {
                                window.location.reload();
                            }, 3000);
                        }
                        // Toastr message is already shown by ajaxRequestWithPromise via displayResponseMessage
                    }).catch(function(error) {
                        // Error handling is done automatically by ajaxRequestWithPromise
                    });
                },
            },
            cancel: {
                text: "Cancel ",
                action: function () { },
            },
        },
    });
}
function convertFormSearilizeToObject(id) {
    let uncleandata = $("#" + id).serializeArray();
    let data = {};
    let queryArr = [];
    uncleandata.forEach((item) => {
        var str1 = item.name;
        var str2 = "[]";
        if (str1.indexOf(str2) != -1) {
            var newname = str1.replace("[]", "");
            var dynamicList = {
                [newname]: item.value,
            };
            queryArr.push(dynamicList);
        } else {
            data[item.name] = item.value;
        }
    });

    if (typeof queryArr !== "undefined" && queryArr.length > 0) {
        data["array_filters"] = queryArr;
    }
    return data;
}



function getCurrentRoute() {
    let pathn = window.location.pathname;
    let route_array = pathn.split("/");
    let length = route_array.length;
    let route = route_array[route_array.length - 1];
    return route;
}


function addRequiredStartToLabel() {
    $('.required').each(function (i) {
        $(this).prev('label').addClass('required-label');
    });
}

addRequiredStartToLabel();

function openSideLayout(data, url, title, width = 75) {
    // Remove ALL TinyMCE editors first
    if (typeof tinyMCE !== 'undefined' && tinyMCE.editors.length > 0) {
        while (tinyMCE.editors.length > 0) {
            tinyMCE.remove(tinyMCE.editors[0]);
        }
    }
    
    // Clear content
    $('#dynamicSideLayoutContent').html('');
    
    // Construct URL - prepend baseURL if not already a full URL
    var fullUrl = url;
    if (url.indexOf('http://') === 0 || url.indexOf('https://') === 0) {
        fullUrl = url;
    } else if (typeof baseURL !== 'undefined' && baseURL) {
        var normalizedBase = (baseURL.charAt(baseURL.length - 1) === '/') ? baseURL : baseURL + '/';
        fullUrl = normalizedBase + url.replace(/^\//, '');
    }
    
    // Set width and trigger offcanvas
    $('.offcanvas-end').css({ width: width + '%', maxWidth: 'none' });
    $('#sidelayoutTriggerButton').trigger('click');
    
    // Set title
    $('.sidelayoutTitle').text(title);
    
    // Load content via AJAX
    var postKey = "sidelayoutContent";
    ajaxRequestPromiseHtml(fullUrl, data, postKey).then(function (response) {
        preloaderOverlay('hide');
        $('#dynamicSideLayoutContent').html(response);
        addRequiredStartToLabel();
    }).catch(function (err) {
        console.log(err);
        preloaderOverlay('hide');
    });
}

function closeSideLayout() {
    // Remove ALL TinyMCE editors
    if (typeof tinyMCE !== 'undefined' && tinyMCE.editors.length > 0) {
        while (tinyMCE.editors.length > 0) {
            tinyMCE.remove(tinyMCE.editors[0]);
        }
    }
    
    // Clear content
    $('#dynamicSideLayoutContent').html('');
    
    // Close offcanvas - trigger the close button (matches CI4 pattern)
    $('.popup-close[data-bs-dismiss="offcanvas"]').trigger('click');
    
    // Fallback if close button not found
    if ($('.popup-close[data-bs-dismiss="offcanvas"]').length === 0) {
        var offcanvasElement = document.getElementById('offcanvasRight');
        if (offcanvasElement) {
            var offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
            if (offcanvasInstance) {
                offcanvasInstance.hide();
            }
        }
    }
}

function clearSideLayout() {
    $('#dynamicSideLayoutContent').html('');
}

function bindDepartmentWorkflowHandlers(options) {
    options = $.extend({
        saveUrl: '',
        statusUrl: '',
        csrfToken: '',
        reloadMode: 'page',
        sidelayoutUrl: '',
        onSuccess: null
    }, options || {});

    $('.save-dept-meta').off('click.deptWorkflow').on('click.deptWorkflow', function() {
        var $btn = $(this);
        var $block = $btn.closest('.dept-meta-form');
        var payload = new FormData();
        $block.find('input, select, textarea').each(function() {
            if (this.name) {
                payload.append(this.name, $(this).val());
            }
        });
        payload.append('_token', options.csrfToken);
        ajaxRequestWithPromise(options.saveUrl, payload, 'save_project_department', 1, '', $btn)
            .then(function(res) { parseFormErrors(res, res.error == 0 ? 'success' : 'error'); });
    });

    $('.dept-action').off('click.deptWorkflow').on('click.deptWorkflow', function() {
        var $btn = $(this);
        var payload = 'project_department_id=' + $btn.data('id') + '&action=' + $btn.data('action') + '&_token=' + options.csrfToken;
        ajaxRequestWithPromise(options.statusUrl, payload, 'update_department_status', 0, '', $btn)
            .then(function(res) {
                parseFormErrors(res, res.error == 0 ? 'success' : 'error');
                if (res.error == 0) {
                    if (typeof options.onSuccess === 'function') {
                        options.onSuccess(res);
                        return;
                    }
                    setTimeout(function() {
                        if (options.reloadMode === 'sidelayout' && options.sidelayoutUrl) {
                            openSideLayout({}, options.sidelayoutUrl, $('.sidelayoutTitle').text());
                        } else if (options.reloadMode === 'page') {
                            window.location.reload();
                        }
                    }, options.reloadMode === 'page' ? 600 : 500);
                }
            });
    });
}

/**
 * Helper function to generate sidelayout edit link HTML
 * Usage: generateSideLayoutEditLink(url, title, iconClass)
 * 
 * @param {string} url - The edit URL
 * @param {string} title - Tooltip title (default: 'Edit')
 * @param {string} iconClass - Icon class (default: 'ri-edit-fill')
 * @returns {string} HTML string for edit link
 */
function generateSideLayoutEditLink(url, title = 'Edit', iconClass = 'ri-edit-fill') {
    return '<a href="javascript:void(0)" ' +
           'onclick="openSideLayout({}, \'' + url + '\', \'' + title + '\'); return false;" ' +
           'data-bs-toggle="tooltip" ' +
           'data-bs-placement="top" ' +
           'title="' + title + '">' +
           '<i class="' + iconClass + '"></i>' +
           '</a>';
}

/**
 * Helper function to generate sidelayout add button HTML
 * Usage: generateSideLayoutAddButton(url, title, buttonText)
 * 
 * @param {string} url - The add URL
 * @param {string} title - Button title/tooltip
 * @param {string} buttonText - Button text (default: 'Add')
 * @returns {string} HTML string for add button
 */
function generateSideLayoutAddButton(url, title, buttonText = 'Add') {
    return '<a href="javascript:void(0)" ' +
           'onclick="openSideLayout({}, \'' + url + '\', \'' + title + '\'); return false;" ' +
           'class="btn btn-primary waves-effect waves-light me-1 createTask mt" ' +
           'data-bs-toggle="tooltip" ' +
           'data-bs-placement="top" ' +
           'title="' + title + '">' +
           '<i class="fas fa-plus fa-fw"></i> ' + buttonText +
           '</a>';
}

// Global fix for TinyMCE dialog tabindex and focus issue in offcanvas
// This runs continuously to fix tabindex and ensure inputs are focusable
(function() {
    function fixTinyMCEDialogTabindex() {
        if (document.querySelector('.offcanvas.show')) {
            // Fix the dialog itself - remove tabindex="-1"
            var dialogs = document.querySelectorAll('.tox-dialog');
            dialogs.forEach(function(dialog) {
                if (dialog.getAttribute('tabindex') === '-1') {
                    dialog.removeAttribute('tabindex');
                }
            });
            
            // Fix all inputs inside dialogs
            var dialogInputs = document.querySelectorAll('.tox-dialog input, .tox-dialog textarea, .tox-dialog select, .tox-textarea');
            dialogInputs.forEach(function(input) {
                // Remove tabindex="-1"
                var tabindex = input.getAttribute('tabindex');
                if (tabindex === '-1') {
                    input.removeAttribute('tabindex');
                    input.setAttribute('tabindex', '0');
                }
                // Remove data-alloy-tabstop which might interfere
                if (input.hasAttribute('data-alloy-tabstop')) {
                    input.removeAttribute('data-alloy-tabstop');
                }
                // Ensure it can receive focus and is interactive
                input.style.pointerEvents = 'auto';
                input.style.userSelect = 'text';
                input.style.webkitUserSelect = 'text';
                input.style.mozUserSelect = 'text';
                input.style.msUserSelect = 'text';
                // Make sure it's focusable
                if (!input.hasAttribute('tabindex')) {
                    input.setAttribute('tabindex', '0');
                }
                // Remove any disabled or readonly attributes
                input.removeAttribute('disabled');
                input.removeAttribute('readonly');
                // Ensure it's not hidden
                input.style.display = '';
                input.style.visibility = '';
            });
            
            // Disable Bootstrap focus trap for TinyMCE dialogs
            var offcanvas = document.querySelector('.offcanvas.show');
            if (offcanvas && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                try {
                    var offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvas);
                    if (offcanvasInstance) {
                        // Temporarily disable focus trap when TinyMCE dialog is open
                        if (document.querySelector('.tox-dialog')) {
                            // Allow focus on TinyMCE dialog elements
                            var originalFocustrap = offcanvasInstance._focustrap;
                            if (originalFocustrap && originalFocustrap._handleFocusin) {
                                var originalHandleFocusin = originalFocustrap._handleFocusin;
                                originalFocustrap._handleFocusin = function(event) {
                                    // Allow focus on TinyMCE dialog elements
                                    if (event.target && (
                                        event.target.closest('.tox-dialog') ||
                                        event.target.closest('.tox-dialog-wrap')
                                    )) {
                                        return;
                                    }
                                    originalHandleFocusin.call(this, event);
                                };
                            }
                        }
                    }
                } catch(err) {
                    console.log('Could not disable focus trap:', err);
                }
            }
        }
    }
    
    // Run immediately and set up observers
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setInterval(fixTinyMCEDialogTabindex, 100);
        });
    } else {
        setInterval(fixTinyMCEDialogTabindex, 100);
    }
    
    // Also watch for dialog creation
    var observer = new MutationObserver(function(mutations) {
        if (document.querySelector('.offcanvas.show') && document.querySelector('.tox-dialog')) {
            fixTinyMCEDialogTabindex();
            // Try to focus textarea if it's the source code editor (but don't change cursor position)
            setTimeout(function() {
                var textarea = document.querySelector('.tox-dialog .tox-textarea');
                if (textarea && !textarea.hasAttribute('data-focused')) {
                    try {
                        // Only focus if not already focused - don't change cursor position
                        if (document.activeElement !== textarea) {
                            textarea.focus();
                            textarea.setAttribute('data-focused', 'true');
                        }
                    } catch(err) {
                        console.log('Focus error:', err);
                    }
                }
            }, 200);
        }
    });
    
    if (document.body) {
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    }
    
    // Also listen for focus events and allow them on TinyMCE dialogs
    document.addEventListener('focusin', function(e) {
        if (document.querySelector('.offcanvas.show') && document.querySelector('.tox-dialog')) {
            var target = e.target;
            if (target && (target.closest('.tox-dialog') || target.closest('.tox-dialog-wrap'))) {
                // Allow focus on TinyMCE dialog elements
                e.stopPropagation();
                e.stopImmediatePropagation();
                return true;
            }
        }
    }, true);
    
    // Override Bootstrap's focus trap to allow TinyMCE dialogs
    document.addEventListener('focusin', function(e) {
        if (document.querySelector('.offcanvas.show') && document.querySelector('.tox-dialog')) {
            var target = e.target;
            // If focus is trying to go to TinyMCE dialog, allow it
            if (target && (target.closest('.tox-dialog') || target.closest('.tox-dialog-wrap'))) {
                // Prevent Bootstrap from trapping focus
                var offcanvas = document.querySelector('.offcanvas.show');
                if (offcanvas && typeof bootstrap !== 'undefined') {
                    try {
                        var offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvas);
                        if (offcanvasInstance && offcanvasInstance._focustrap) {
                            // Temporarily disable focus trap
                            var originalTrap = offcanvasInstance._focustrap;
                            if (originalTrap && originalTrap._handleFocusin) {
                                // Don't call the original handler for TinyMCE elements
                                e.stopImmediatePropagation();
                                return false;
                            }
                        }
                    } catch(err) {}
                }
            }
        }
    }, true);
    
    // Add click handler to allow focus on textarea (but preserve cursor position)
    document.addEventListener('click', function(e) {
        if (document.querySelector('.offcanvas.show') && document.querySelector('.tox-dialog')) {
            var target = e.target;
            // If clicking on textarea or its container, allow focus but don't change cursor position
            if (target && (target.classList.contains('tox-textarea') || target.closest('.tox-textarea'))) {
                var textarea = target.classList.contains('tox-textarea') ? target : target.closest('.tox-textarea');
                if (textarea) {
                    // Don't interfere with the click - let it set cursor position naturally
                    // Just ensure focus is allowed
                    setTimeout(function() {
                        try {
                            // Only focus if not already focused - preserve user's click position
                            if (document.activeElement !== textarea) {
                                textarea.focus();
                            }
                        } catch(err) {}
                    }, 1);
                }
            }
        }
    }, false); // Use false (bubble phase) instead of true (capture phase) to not interfere
})();

function openimage(img) {
    $(".identity").attr("src", img);
    $("#imageModalTitle").text('Image Preview');
    $("#imageModal").modal("show");
}

$(document).on("change","#callout_exist",function(e){
    e.preventDefault();
   
    switch ($(this).val()) {
        case "limited":
            $("#limited-div").removeClass("d-none");
            $("#callouts-div").removeClass("d-none");
            break;
        case "unlimited":
            $("#limited-div").addClass("d-none");
            $("#callouts-div").removeClass("d-none");
            $("#no_of_callouts").val("");
            break;
        default:
            $("#limited-div").addClass("d-none");
            $("#callouts-div").addClass("d-none");
            $("#no_of_callouts").val("");
            break;
    }
});
function handleChange(input,limit) {
    if (input.value < 0) input.value = 0;
    if (input.value > limit) {
        input.value = limit;
    }
}
function showCalloutBox(attr_id){
    var value = $("#callout_"+attr_id+" option:selected").val();
    if(value=='Limited'){
        $(".noofcallouts_"+attr_id).addClass('required');
        $(".calloutbox_"+attr_id).removeClass('d-none');
    }else{
        $(".noofcallouts_"+attr_id).val('');
        $(".calloutbox_"+attr_id).addClass('d-none');
        $(".noofcallouts_"+attr_id).removeClass('required');
    }
}


function deleteMappingData(
    rowid,
    rowstatus,
    tableName,
    targetTable,
    col = ""
) {
    $.alert({
        title: "Confirmation",
        content: " Are you sure to delete this row? ",
        rtl: true,
        closeIcon: true,
        buttons: {
            confirm: {
                text: "Confirm ",
                btnClass: "btn-blue",
                action: function () {
                    let id = rowid;
                    let status = '';
                    let table = tableName;
                    let msg = "";            
                    msg = "Row deleted successfully!!";
                    let input = {
                        id: id,
                        status: '',
                        table: table,
                        token: $('meta[name="csrf-token"]').attr("content"),
                    };
                    // Note: ajaxRequestWithPromise will automatically show toastr messages
                    ajaxRequestWithPromise(baseURL + "delete_mapping", input, 'delete_mapping', 0).then(function(response) {
                        ajaxDataTableReload(targetTable);
                        // Toastr message is already shown by ajaxRequestWithPromise via displayResponseMessage
                    }).catch(function(error) {
                        // Error handling is done automatically by ajaxRequestWithPromise
                    });
                },
            },
            cancel: {
                text: "Cancel ",
                action: function () { },
            },
        },
    });
}


function usedMaterials(date, item_id, user_id,proposal_id='',search_type='') {

    // let url = "{{ url('/view_used_material')}}";
    let url = baseURL + "view_used_material";
    $("#customeModal").modal('show');
    $(".displayCustomContent").html('');
    $("#customeModalLabel").text('Consumption Materials');
    let data = {
        daterange   : date,
        item_id     : item_id,
        user_id     : user_id,
        proposal_id : proposal_id,
        search_type : search_type
    };
    response = ajaxRequestPromise(url, data);
    response.then(function(v) {
        // console.log(v);
        var res = JSON.parse(v);
        //console.log(res.data);
        if (res.error == 0) {
            $(".displayCustomContent").html(res.data);
        } else if (res.error == 2) {
            parseFormErrors(res, 'error');
        } else {
            parseFormErrors(res, 'error');
        }
    }, function(e) {
        console.log(e);
    });
}




/*function requestDiscountProposal(
    rowid,
    rowstatus='',
    tableName='',
    targetTable,
    col = ""
    ) {
        $.alert({
            title: "Request for Discount",
            content: '' +
            '<form action="" class="formName">' +
            '<div class="form-group">' +
            '<label>Enter Remarks:</label>' +
            '<input name ="" class="remark form-control" placeholder="Enter Remarks">' +
            '</div>' +
            '</form>',
            rtl: true,
            closeIcon: true,
            buttons: {
                confirm: {
                    text: "Confirm ",
                    btnClass: "btn-blue",
                    action: function () {
                        var dFr = this.$content.find('.remark').val();
                        var proposal_no = $('#proposal_no').val();
                        if(!dFr){
                            $.alert('Please Provide Remarks');
                            return false;
                        }
                        let id = rowid;
                        let status = rowstatus;
                        let table = tableName;
                        let msg = "";
                        msg = 'Discount request sent successfully!!';
                        let input = {
                            id: id,
                            remarks: dFr,
                            proposal_no: proposal_no,
                            table: table,
                            token: $('meta[name="csrf-token"]').attr("content"),
                        };
                        // Note: ajaxRequestWithPromise will automatically show toastr messages
                        ajaxRequestWithPromise(baseURL + "discountrequest", input, 'discount_request', 0).then(function(response) {
                            location.reload();
                            // Toastr message is already shown by ajaxRequestWithPromise via displayResponseMessage
                        }).catch(function(error) {
                            // Error handling is done automatically by ajaxRequestWithPromise
                        });
                    },
                },
                cancel: {
                    text: "Cancel ",
                    action: function () {},
                },
            },
        });
    }
    
    
    function approveProposal(
        rowid,
        rowstatus='',
        tableName='',
        targetTable,
        col = ""
        ) {
            $.alert({
                title: "Proposal Approval",
                content: '' +
                '<form action="" class="formName">' +
                '<div class="form-group">' +
                '<label>Enter Remarks:</label>' +
                '<input name ="" class="remark form-control" placeholder="Enter Remarks">' +
                '</div>' +
                '</form>',
                rtl: true,
                closeIcon: true,
                buttons: {
                    confirm: {
                        text: "Confirm ",
                        btnClass: "btn-blue",
                        action: function () {
                            var dFr = this.$content.find('.remark').val();
                            if(!dFr){
                                $.alert('Please Provide Remarks');
                                return false;
                            }
                            let id = rowid;
                            let status = rowstatus;
                            let table = tableName;
                            let msg = "";
                            /*if (status == 3) {
                                status = 4;
                                msg = "Status De-Activated successfully!!";
                            } else {
                                status = 3;
                                msg = "Status Activated successfully!!";
                            }
                            msg = 'Proposal Approved successfully!!';
                            let input = {
                                id: id,
                                remarks: dFr,
                                table: table,
                                token: $('meta[name="csrf-token"]').attr("content"),
                            };
                            // Note: ajaxRequestWithPromise will automatically show toastr messages
                            ajaxRequestWithPromise(baseURL + "updateproposalstatus", input, 'update_proposal_status', 0).then(function(response) {
                                // Toastr message is already shown by ajaxRequestWithPromise via displayResponseMessage
                            }).catch(function(error) {
                                // Error handling is done automatically by ajaxRequestWithPromise
                            });
                        },
                    },
                    cancel: {
                        text: "Cancel ",
                        action: function () {},
                    },
                },
            });
        }*/


function initializeDaterangepickerlatest(selector) {
    let mindate = new Date(new Date().getFullYear()-1, 0, 1);
    let maxdate = new Date(new Date().getFullYear(), 11, 31);
    $(selector).daterangepicker({
        defaultDate: new Date(),
        opens: 'left',
        minDate: mindate,
        maxDate: maxdate,
        locale: {
            format: 'YYYY/MM/DD'
        }
    }, function(start, end, label) {
        console.log('Selected rangeNewn: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
    });
}

function applyNoZeroValidation() {
    $(document).on('input', '.no-zero-input', function() {
        var value = $(this).val();
        if (value === '0') {
            $(this).val(''); // Clear the input
            $(this).attr('placeholder', 'Payment should be greater than 0'); // Set placeholder message
            $(this).addClass('error'); // Add error class for styling
        } else {
            $(this).removeClass('error'); // Remove error class if input is valid
            $(this).attr('placeholder', 'Enter a number'); // Reset placeholder
            return 1;
        }
    });
}
    
function copyClipboard(copyText){
    // Prefer modern Clipboard API when available
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        navigator.clipboard.writeText(copyText)
            .then(function() {
                toastr.success('Copied to clipboard');
            })
            .catch(function() {
                // Fallback to legacy approach
                legacyCopyClipboard(copyText);
            });
        return;
    }
    legacyCopyClipboard(copyText);
}

function legacyCopyClipboard(copyText) {
    let tempInput = $('<textarea>');
    $('body').append(tempInput);
    tempInput.val(copyText).select(); // Set the text and select it
    // Copy the text to the clipboard
    if (document.execCommand('copy')) {
        toastr.success('Copied to clipboard');
    } else {
        toastr.error('Failed to copy');
        console.log('Failed to copy.');
    }
    // Remove the temporary textarea
    tempInput.remove();
}

// Global Icons helper (admin header): click an item to copy its icon class
$(document).on('click', '.icon-copy-item', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var iconClass = $(this).data('icon');
    if (iconClass) {
        // Use project utility (now supports Clipboard API + fallback)
        if (typeof copyClipboard === 'function') {
            copyClipboard(iconClass);
        } else if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            navigator.clipboard.writeText(iconClass)
                .then(function() { toastr.success('Copied to clipboard'); })
                .catch(function() { console.log('Failed to copy'); });
        }
    }
});

function deleteTableRecord(
    rowid,
    deleteStatus,
    tableName,
    targetTable,
    col = ""
) {
    var content =" Would you like to delete this record ? " +
      '<br><form action="" class="formName">' +
      '<div class="form-group mt-2">' +
      '<textarea placeholder="Please Enter Remarks" class="form-control remarks" required /></textarea>' +
      "</div>" +
      "</form>";
    $.alert({        
        title: "<div class='custom-h5'>Confirmation</div>",
        content: content,
        rtl: true,
        closeIcon: true,
        buttons: {
            confirm: {
                text: "Confirm ",
                btnClass: "btn-blue",
                action: function () {
                    var remark = this.$content.find(".remarks").val();
                    if (!remark) {
                        $.alert("Please Provide the Remarks");
                        return false;
                    }
                    let id = rowid;
                    let table = tableName;
                    let msg = "";
                    if (deleteStatus == 0) {
                        deleteStatus = 1;     
                    } else if(deleteStatus == 1){
                        deleteStatus = 0;
                    }else{
                        deleteStatus = deleteStatus;
                    }
                    $(this).val(deleteStatus);
                    input = {
                        id: id,
                        deleteStatus: deleteStatus,
                        table: table,
                        remarks: remark,
                        token: $('meta[name="csrf-token"]').attr("content"),
                    };
                    // Note: ajaxRequestWithPromise will automatically show toastr messages
                    ajaxRequestWithPromise(baseURL + "/delete-record", input, 'delete_record', 0).then(function(response) {
                        var res = typeof response === 'string' ? JSON.parse(response) : response;
                        ajaxDataTableReload(targetTable);
                        // Toastr message is already shown by ajaxRequestWithPromise via displayResponseMessage
                    }).catch(function(error) {
                        // Error handling is done automatically by ajaxRequestWithPromise
                    });
                },
            },
            cancel: {
                text: "Cancel ",
                action: function () { },
            },
        },
    });
}

function checkIsContractCreated(rowid,
    deleteStatus,
    tableName,
    targetTable,
    col = "",
    contract_generate='')
{
    if(contract_generate == '1')
    {
        $.alert({
            title: "Alert!",
            content:"Contract created to this proposal you can't delete this proposal",
        });
        return false;
    }

    deleteTableRecord(rowid,deleteStatus,tableName,targetTable,col);
}

