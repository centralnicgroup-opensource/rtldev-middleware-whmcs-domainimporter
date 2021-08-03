$(document).ready(() => {
    let lenOrginal;
    let form = $('#backform');
    let domains = form.find('input[name="domains"]').val().split("\n");
    let data = {
        clientid: form.find('input[name="clientid"]').val(),
        toClientImport: form.find('input[name="toClientImport"]').val(),
        gateway: form.find('input[name="gateway"]').val(),
        currency: form.find('input[name="currency"]').val(),
        registrar: form.find('input[name="registrar"]').val(),
        action: 'importsingle'
    };
    // Adjust the width of thead cells with width of tbody cells when window resizes
    $(window).resize(function () {
        const $table = $('table.scrollable');
        const $bodyCells = $table.find('tbody tr:first').children();
        const colWidth = $bodyCells.map(function () {
            return $(this).width();
        }).get();
        if (colWidth.length) {
            $table.find('thead tr').children().each(function (i, v) {
                $(v).width(colWidth[i]);
            });
        }
    });

    const showResultContinue = (res, idnconv, isretry) => {
        // output last import result
        console.dir(res);
        if (res.success) {
            $(`tr[data-pc='${idnconv.PC}'] td.result`).html(
                `<span class="label label-success" role="alert">${res.msg}</span>`
            );
            if (res.hasTrustee) {
                $(`tr[data-pc='${idnconv.PC}'] td.result`).append(
                    `&nbsp;<span class="label label-warning" role="alert">${translate("trusteeservice")}</span>`
                );
            }
        } else {
            $(`tr[data-pc='${idnconv.PC}'] td.result`).html(`<span class="label label-danger" role="alert">${res.msg}</span>`);
            if (res.allowretry) {
                $(`tr[data-pc='${idnconv.PC}'] td.action`).html(`<button type="button" class="bttn bttn-primary bttn-sm retryimport">${translate("bttn.retryimport")}</button>`);
                $(`tr[data-pc='${idnconv.PC}'] button.retryimport`).on('click', retryImport);
            }
        }
        

        // update progress bar
        if (!isretry) {
            const lenNow = domains.length;
            const progress = lenOrginal - lenNow;
            const html = `${Math.round(progress / (lenOrginal / 100))}%`;
            $('#counterleft')
                .html(html)
                .css('width', html)
                .attr('aria-valuenow', progress);
            // continue importing domains
            importDomain();
        } else {
            isFinishedImport();
        }

        // update view
        $(window).resize();
    };

    const isFinishedImport = function(){
        if (!domains.length) {
            $("#inprogress").html(`${translate("status.importdone")}.`);
            return true;
        }
        return false;
    }

    const retryImport = function(){
        const $row = $(this).closest('tr');
        const domain = $row.data('pc');
        $(this).closest('td').html('');
        $row.find('td.result').html('<span class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span>');
        importDomain(domain);
    };

    const importDomain = (domain = "") => {
        const retry = !!domain.length;
        let result;
        if (retry) { // retry import
            result = ispapiIdnconverter.convert(domain);
            if (!domains.length) {
                //create line with spinner icon before import request will be sent
                $("#inprogress").html(`${translate("status.importing")} <b>${result.IDN}</b> ...`);
                $(window).resize();
            }
        } else { // bulk import
            if (isFinishedImport()) {
                return;
            }
            domain = domains.shift();
            result = ispapiIdnconverter.convert(domain);
            //create line with spinner icon before import request will be sent
            $("#inprogress").html(`${translate("status.importing")} <b>${result.IDN}</b> ...`);
            $("#importresults").append(`
                <tr data-idn="${result.IDN}" data-pc="${result.PC}">
                    <td>${result.IDN}</td>
                    <td>${(result.IDN !== result.PC) ? `<small>${result.PC}</small>` : ''}</td>
                    <td class="result"><span class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span></td>
                    <td class="action"></td>
                </tr>`
            );
            $(window).resize();
        }
        
        (function(data, result, retry) {
            $.ajax({
                data: {
                    ...data,
                    idn: result.IDN,
                    pc: result.PC
                },
                dataType: 'json',
                type: 'POST'
            }).then((d) => {
                //successful http communication, use returned result for output
                showResultContinue(d, result, retry);
            }, (d) => {
                //failed http communication, show error
                showResultContinue({
                    success: false,
                    msg: `${d.status} ${d.statusText}`
                }, result, retry);
            });
        }(data, result, retry));
    };

    const translate = (translationkey) => {
        if (translations.hasOwnProperty(translationkey)) {
            return translations[translationkey];
        }
        return translationkey;
    };

    domains = domains.filter((domain, idx) => {
        domain = domain.replace(/\s/g, "");
        if (!domain.length) {
            return false;
        }
        const result = ispapiIdnconverter.convert(domain);
        domain = result.IDN;
        if (!domain.length || /^xn--.+$/.test(domain)) {
            $("#importresults").append(`
                <tr>
                    <td>${domain}</td>
                    <td></td>
                    <td class="result">
                        <span class="label label-danger" role="alert">${translate('domainnameinvaliderror')}</span>
                    </td>
                    <td class="action"></td>
                </tr>
            `);
            $(window).resize();
            return false;
        }
        return true;
    });
    if (!domains.length) {
        return;
    }
    lenOrginal = domains.length;
    $('#counterleft').attr('aria-valuemax', lenOrginal);
    importDomain();
});