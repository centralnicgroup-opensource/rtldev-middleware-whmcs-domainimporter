const ta = $("#domains");
const showNumber = () => {
  let count = 0;
  if (ta.val() !== "") {
    count = ta.val().split("\n").length;
  }
  const eL = $("#labeldomains");
  eL.text(eL.text().replace(/\s?\([0-9]+\)$/, ""));
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
  $("div.clientdetails").css("display", "");
  if (d.clientdetails && d.clientdetails.length) {
    $("#clientdetailscont").html(d.clientdetails);
  } else {
    $("#clientdetailscont").html(
      `<span class="label label-danger">${d.msg}</span>`
    );
  }
};
const loadClientDetails = function () {
  const $eL = $(this);
  const status = $eL.prop("disabled");
  if (status === true || $eL.val() === "") {
    return;
  }
  $.ajax({
    type: "POST",
    data: {
      module: "cnicdomainimport",
      action: "getclientdetails",
      clientid: $eL.val(),
    },
    dataType: "json",
  }).then(
    (d) => {
      //successful http communication, use returned result for output
      showClientDetails(d);
    },
    (d) => {
      //failed http communication, show error
      showClientDetails({
        success: false,
        msg: `${d.status} ${d.statusText}`,
      });
    }
  );
};
$("#clientid").on("input", loadClientDetails);

$("#toClientImport").click(function () {
  const $eL = $('#importform input[name="clientid"]');
  const status = $eL.prop("disabled");
  $eL.prop("disabled", !status);
  if (!status) {
    $eL.val("");
    $("div.clientdetails").css("display", "");
    $("#clientdetailscont").html("");
  }
});

$("#importform").submit(function (event) {
  if ($("#domains").val() === "" || $("#registrar").val() === "") {
    event.preventDefault();
  }
});

var Upload = function (file) {
  this.file = file;
};

Upload.prototype.getType = function () {
  return this.file.type;
};
Upload.prototype.getSize = function () {
  return this.file.size;
};
Upload.prototype.getName = function () {
  return this.file.name;
};
Upload.prototype.doUpload = function () {
  var that = this;
  var formData = new FormData();

  // add assoc key values, this will be posts values
  formData.append("file", this.file, this.getName());
  formData.append("upload_file", true);
  formData.append("method", "cnicdomainimport");
  formData.append("ajax", true);
  formData.append("action", "importcsv");
  $.ajax({
    type: "POST",
    xhr: function () {
      var myXhr = $.ajaxSettings.xhr();
      if (myXhr.upload) {
        myXhr.upload.addEventListener("progress", that.progressHandling, false);
      }
      return myXhr;
    },
    success: function (data) {
      console.log("error");
      console.table(data);
      if (!data.status || data.empty) {
        $("#progress-upload").addClass("hide");
        $("#uploadNotes").removeClass("hide");
        $("#uploadNotes").html(data.msg);
      }
      // successful http communication, use returned result for output
      let finalDisplay = "";
      data.domainlist.forEach((domains) => {
        finalDisplay += domains.domain + "\r\n";
      });
      var rTrim = function (input) {
        var rTrimRegex = new RegExp("\r\n$");
        return input.replace(rTrimRegex, "");
      };
      try {
        finalDisplay = decodeURIComponent(escape(rTrim(finalDisplay)));
      } catch (err) {
        $("#progress-upload").addClass("hide");
        $("#uploadNotes").removeClass("hide");
        $("#uploadNotes").html("Invalid file type!");
        return;
      }
      $("#domains").val(finalDisplay);
      showNumber();
    },
    error: function (error) {
      console.log("error");
      // handle error
      $("#uploadNotes").html(error);
    },
    async: true,
    data: formData,
    cache: false,
    contentType: false,
    processData: false,
    dataType: "json",
  });
};

Upload.prototype.progressHandling = function (event) {
  var percent = 0;
  var position = event.loaded || event.position;
  var total = event.total;
  var progress_bar_id = "#progress-upload";
  if (event.lengthComputable) {
    percent = Math.ceil((position / total) * 100);
  }
  // update progressbars classes so it fits your code
  $(progress_bar_id).removeClass("hide");
  $(progress_bar_id + " .progress-bar").css("width", +percent + "%");
  $(progress_bar_id).css("margin-top", "5px");
  $(progress_bar_id + " .progress-bar").attr("aria-valuenow", percent);
  if (percent == 100) {
    $(progress_bar_id + " .progress-bar").html(+percent + "% Complete");
    $(progress_bar_id + " .progress-bar").removeClass("active");
  }
};

//Change id to your id
$("#importUsingFile").on("change", function (e) {
  $("#uploadNotes").addClass("hide");
  $("#uploadNotes").empty();
  var file = $(this)[0].files[0];
  var ext = $(this).val().split(".").pop().toLowerCase();
  if (ext == "") {
    return;
  }
  if ($.inArray(ext, ["CSV", "csv"]) == -1) {
    $("#uploadNotes").removeClass("hide");
    $("#uploadNotes").html("invalid extension only .CSV files are allowed!");
    return;
  }

  var upload = new Upload(file);

  // maby check size or type here with upload.getSize() and upload.getType()

  // execute upload
  upload.doUpload();
});
