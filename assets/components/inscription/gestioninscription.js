const Toast = Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener("mouseenter", Swal.stopTimer);
    toast.addEventListener("mouseleave", Swal.resumeTimer);
  },
});
let id_inscription = false;
let idInscription = [];
let frais = [];
let facture_exist = false;
$(document).ready(function () {
  var table = $("#datatables_gestion_inscription").DataTable({
    lengthMenu: [
      [10, 15, 25, 50, 100, 20000000000000],
      [10, 15, 25, 50, 100, "All"],
    ],
    order: [[0, "desc"]],
    ajax: "/inscription/gestion/list",
    processing: true,
    serverSide: true,
    deferRender: true,
    responsive: true,
    scrollX: true,
    drawCallback: function () {
      idInscription.forEach((e) => {
        $("body tr#" + e)
          .find("input")
          .prop("checked", true);
      });
      $("body tr#" + id_inscription).addClass("active_databales");
    },
    language: {
      url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
    },
  });

  const getStatutInscription = async () => {
    const icon = $("#statut-modal i");
    try {
      icon.removeClass("fa-check").addClass("fa-spinner fa-spin");
      const request = await axios.get(
        "/inscription/gestion/getstatut/" + id_inscription
      );
      const data = await request.data;
      $("#statut_inscription").html(data).select2();
    } catch (error) {
      const message = error.response.data;
      console.log(error, error.response);
      Toast.fire({
        icon: "error",
        title: "Some Error",
      });
    }
    icon.addClass("fa-check").removeClass("fa-spinner fa-spin");
  };
  $("#frais").on("change", () => {
    $("#montant").val($("#frais").find(":selected").data("frais"));
  });
  const getOrganisme = async () => {
    try {
      const request = await axios.get("/api/organisme");
      const data = await request.data;
      $("#organisme").html(data).select2();
    } catch (error) {
      const message = error.response.data;
      console.log(error, error.response);
      Toast.fire({
        icon: "error",
        title: "Some Error",
      });
    }
  };
  getOrganisme();
  $("#etablissement").select2();
  $("#etablissement").on("change", async function () {
    const id_etab = $(this).val();
    table.columns().search("");
    table.columns(0).search(id_etab).draw();
    let response = "";
    if (id_etab != "") {
      const request = await axios.get("/api/formation/" + id_etab);
      response = request.data;
    } else {
      $("#annee").html("").select2();
      $("#promotion").html("").select2();
    }
    $("#formation").html(response).select2();
  });
  $("#formation").on("change", async function () {
    const id_formation = $(this).val();
    table.columns().search("");
    let responseAnnee = "";
    let responsePromotion = "";
    if (id_formation != "") {
      table.columns(1).search(id_formation).draw();
      const requestPromotion = await axios.get(
        "/api/promotion/" + id_formation
      );
      responsePromotion = requestPromotion.data;
      const requestAnnee = await axios.get("/api/annee/" + id_formation);
      responseAnnee = requestAnnee.data;
    } else {
      table.columns(0).search($("#etablissement").val()).draw();
    }
    $("#annee").html(responseAnnee).select2();
    $("#promotion").html(responsePromotion).select2();
  });

  $("#promotion").on("change", async function () {
    table.columns().search("");
    if ($(this).val() != "") {
      if ($("#annee").val() != "") {
        table.columns(3).search($("#annee").val());
      }
      table.columns(2).search($(this).val()).draw();
    } else {
      table.columns(1).search($("#formation").val()).draw();
    }
  });
  $("#annee").on("change", async function () {
    table.columns().search("");
    if ($(this).val() != "") {
      table.columns(3).search($(this).val());
    }
    table.columns(2).search($("#promotion").val()).draw();
  });

  $("body").on(
    "click",
    "#datatables_gestion_inscription tbody tr",
    function () {
      const input = $(this).find("input");
      if (input.is(":checked")) {
        input.prop("checked", false);
        const index = idInscription.indexOf(input.attr("id"));
        idInscription.splice(index, 1);
      } else {
        input.prop("checked", true);
        idInscription.push(input.attr("id"));
      }
    }
  );
  $("body").on(
    "dblclick",
    "#datatables_gestion_inscription tbody tr",
    function () {
      // const input = $(this).find("input");

      if ($(this).hasClass("active_databales")) {
        $(this).removeClass("active_databales");
        id_inscription = null;
      } else {
        $("#datatables_gestion_inscription tbody tr").removeClass(
          "active_databales"
        );
        $(this).addClass("active_databales");
        id_inscription = $(this).attr("id");
        getStatutInscription();
      }
    }
  );
  const getFrais = async () => {
    try {
      const request = await axios.get(
        "/inscription/gestion/frais/" + id_inscription
      );
      const data = await request.data;
      facture_exist = 1;
      $("#frais").html(data.list).select2();
      $("#code-facture").html(data.codefacture);
    } catch (error) {
      const message = error.response.data;
      facture_exist = false;
      Toast.fire({
        icon: "error",
        title: "Facture Introuvable!",
      });
      return;
      // console.log(error, error.response);
      Toast.fire({
        icon: "error",
        title: message,
      });
    }
  };
  const getInscriptionInfos = async () => {
    try {
      const icon = $("#frais-modal i");
      icon.removeClass("fa-money-bill-alt").addClass("fa-spinner fa-spin");
      const request = await axios.get(
        "/inscription/gestion/info/" + id_inscription
      );
      const data = await request.data;
      $(".etudiant_info").html(data);
      icon.addClass("fa-money-bill-alt").removeClass("fa-spinner fa-spin");
      $("#frais_inscription-modal").modal("show");
    } catch (error) {
      const message = error.response.data;
      console.log(error, error.response);
      Toast.fire({
        icon: "error",
        title: "Some Error",
      });
      icon.addClass("fa-money-bill-alt").removeClass("fa-spinner fa-spin");
    }
  };
  $("#frais-modal").on("click", () => {
    if (!id_inscription) {
      Toast.fire({
        icon: "error",
        title: "Veuillez selection une ligne!",
      });
      return;
    }
    // if(!facture_exist){
    //     Toast.fire({
    //       icon: 'error',
    //       title: 'Facture Introuvable!',
    //     })
    //     return;
    // }
    getFrais();
    getInscriptionInfos();
  });

  $("input[type=radio][name=organ]").on("change", async function (e) {
    e.preventDefault();
    if (this.value == 0) {
      const request = await axios.get("/api/getorganismepasPayant");
      response = request.data;
      $(".select-organ #org").html(response).select2();
      $(".select-organ").css("display", "block");
    } else {
      $(".select-organ #org").html("");
      $(".select-organ").css("display", "none");
    }
  });

  $("#add_frais_gestion").on("click", () => {
    let fraisId = $("#frais").find(":selected").val();
    if (fraisId != "") {
      let fraisText = $("#frais").find(":selected").text();
      let prix = $("#montant").val();
      let ice = $("#ice").val();
      let organ = $(".select-organ #org").find(":selected").text();
      let organisme_id = $(".select-organ #org").val();
      if (!$.isNumeric(fraisId) || prix == "") {
        return;
      }
      if ($("input[name='organ']:checked").val() == 1) {
        organisme_id = 7;
        organ = "Payant";
      } else if (organisme_id == "") {
        return;
      }
      frais.push({
        index: Math.floor(Math.random() * 1000 + 1),
        id: fraisId,
        designation: fraisText,
        montant: prix,
        ice: ice,
        organisme_id: organisme_id,
        organisme: organ,
      });
      rawFrais();
    }
  });
  $("body").on("click", ".delete_frais", function () {
    const index = frais.findIndex((frais) => frais.index == $(this).attr("id"));
    frais.splice(index, 1);
    rawFrais();
  });
  const rawFrais = () => {
    let html = "";
    frais.map((f, i) => {
      html += `
            <tr>
                <td>${i + 1}</td>
                <td>${f.designation}</td>
                <td>${f.montant}</td>
                <td>${f.ice}</td>
                <td>${f.organisme}</td>
                <td><button class='delete_frais btn btn-danger'  id='${
                  f.index
                }'><i class='fa fa-trash' ></i></button></td>
            </tr>
        `;
    });
    // console.log(html);
    $(".table_frais_inscription").html(html);
  };

  $("#save_frais_gestion").on("click", async () => {
    let formData = new FormData();
    formData.append("frais", JSON.stringify(frais));
    // formData.append("organisme", $("#organisme").val())
    let modalAlert = $("#frais_inscription-modal .modal-body .alert");

    modalAlert.remove();
    const icon = $("#save_frais_gestion i");
    icon.removeClass("fa-check-circle").addClass("fa-spinner fa-spin");

    try {
      const request = await axios.post(
        "/inscription/gestion/addfrais/" + id_inscription,
        formData
      );
      const response = request.data;
      $("#frais_inscription-modal .modal-body").prepend(
        `<div class="alert alert-success">
                <p>Bien Enregistre</p>
              </div>`
      );
      icon.addClass("fa-check-circle").removeClass("fa-spinner fa-spin ");
      $(".table_frais_inscription").empty();
      frais = [];
      window.open("/inscription/gestion/facture/" + response, "_blank");
      table.ajax.reload(null, false);
    } catch (error) {
      const message = error.response.data;
      console.log(error, error.response);
      modalAlert.remove();
      $("#frais_inscription-modal .modal-body").prepend(
        `<div class="alert alert-danger">${message}</div>`
      );
      icon.addClass("fa-check-circle").removeClass("fa-spinner fa-spin ");
    }
    setTimeout(() => {
      $("#frais_inscription-modal .modal-body .alert").remove();
    }, 3000);
  });

  $("#statut-modal").on("click", () => {
    if (!id_inscription) {
      Toast.fire({
        icon: "error",
        title: "Veuillez selection une ligne!",
      });
      return;
    }
    $("#statut_modal .modal-body .alert").remove();
    $("#statut_modal").modal("show");
  });

  $("#statut_save").on("submit", async function (e) {
    e.preventDefault();
    let formData = new FormData($(this)[0]);
    let modalAlert = $("#statut_modal .modal-body .alert");

    modalAlert.remove();
    const icon = $("#statut_save .btn i");
    icon.removeClass("fa-check-circle").addClass("fa-spinner fa-spin");

    try {
      const request = await axios.post(
        "/inscription/gestion/updatestatut/" + id_inscription,
        formData
      );
      const response = request.data;
      $("#statut_modal .modal-body").prepend(
        `<div class="alert alert-success">
                <p>${response}</p>
              </div>`
      );
      icon.addClass("fa-check-circle").removeClass("fa-spinner fa-spin ");
      $("#annee_inscription, #promotion_inscription").empty();
      table.ajax.reload(null, false);
    } catch (error) {
      const message = error.response.data;
      console.log(error, error.response);
      modalAlert.remove();
      $("#statut_modal .modal-body").prepend(
        `<div class="alert alert-danger">${message}</div>`
      );
      icon.addClass("fa-check-circle").removeClass("fa-spinner fa-spin ");
    }
  });
  $("body").on("click", "#extraction", function () {
    window.open("/inscription/gestion/extraction_ins", "_blank");
  });
});
