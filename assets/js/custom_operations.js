// const { add } = require("lodash");

$(document).on('click','.remove',function(){
    var rowid = $(this).attr('rowid');
    $("#row"+rowid).remove();
});

function openUrl(url){
    console.log(url);
    if(url!='' || url!=NULL){
        window.open(url);
    }
}
