const ta = $("#domains");
const showNumber = () => {
    let count = 0;
    if (ta.val() !== "") {
        count = ta.val().split("\n").length;
    }
    const eL = $("#labeldomains");
    eL.text(eL.text().replace(/\s?\([0-9]+\)$/, ''));
    eL.text(eL.text() + `(${count})`);
};

const parseDomains = () => {
    showNumber();
    let val = ta.val();
    val = val.replace(/(\r\n|,|;)/g, "\n");
    ta.val(val);
};
$("#domains").change(parseDomains);
parseDomains();

const showClientDetails = (d) => {
    $("div.clientdetails").css('display', '');
    if (d.clientdetails && d.clientdetails.length) {
        $("#clientdetailscont").html(d.clientdetails);
    } else {
        $("#clientdetailscont").html(`<span class="label label-danger">${d.msg}</span>`);
    }
};
const loadClientDetails = function(){
    const $eL = $(this);
    const status = $eL.prop('disabled');
    if (status === true || $eL.val()==="") {
        return;
    }
    $.ajax({
        type: "POST",
        data: { 
            module: "cnicdomainimport",
            action: "getclientdetails",
            clientid: $eL.val() 
        },
        dataType: 'json'
    }).then((d) => {
        //successful http communication, use returned result for output
        showClientDetails(d);
    }, (d) => {
        //failed http communication, show error
        showClientDetails({
            success: false,
            msg: `${d.status} ${d.statusText}`
        });
    });
};
$('#clientid').on('input', loadClientDetails);

$('#toClientImport').click(function(){
    const $eL = $('#importform input[name="clientid"]');
    const status = $eL.prop('disabled');
    $eL.prop('disabled', !status);
    if (!status) {
        $eL.val('');
        $('div.clientdetails').css('display', '');
        $("#clientdetailscont").html('');
    }
});

$('#importform').submit(function( event ) {
    if (
        ($( "#domains" ).val() === "")
        || ($( "#registrar" ).val() === "")
    ){
        event.preventDefault();  
    }
});
