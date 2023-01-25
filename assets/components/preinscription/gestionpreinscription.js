$(document).ready(function () {
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    },
})
let id_preinscription = false;
let idpreins = [];
let frais = [];
// var table_preins = $("#datables_preinscription").DataTable({
//     lengthMenu: [
//         [10, 15, 25, 50, 100, 20000000000000],
//         [10, 15, 25, 50, 100, "All"],
//     ],
//     order: [[0, "desc"]],
//     ajax: "/preinscription/list",
//     processing: true,
//     serverSide: true,
//     deferRender: true,
//     language: {
//     url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
//     },
// });

var table_gestion_preins = $("#datables_gestion_preinscription").DataTable({
    lengthMenu: [
        [10, 15, 25, 50, 100, 20000000000000],
        [10, 15, 25, 50, 100, "All"],
    ],
    order: [[1, "desc"]],
    ajax: "/preinscription/gestion/list/gestion_preinscription/",
    processing: true,
    serverSide: true,
    deferRender: true,
    scrollX: true,
    drawCallback: function () {
        idpreins.forEach((e) => {
            $("body tr#" + e)
            .find("input")
            .prop("checked", true);
        });
    },
    language: {
        url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
    },
});
const getDocumentsPreins = async () => {
    try {
        const icon = $('#doc_preinscription i')
        icon.removeClass('fa-check').addClass('fa-spinner fa-spin')
        const request = await axios.get("/preinscription/gestion/getdoc_preinscription/"+id_preinscription);
        const data = await request.data;
        $('.ms-selectable .ms-list').html(data.documents)
        $('.ms-selection .ms-list').html(data.documentsExists)
        icon.addClass('fa-check').removeClass('fa-spinner fa-spin')
    } catch (error) {
        const message = error.response.data;
        console.log(error, error.response);
        Toast.fire({
            icon: 'error',
            title: 'Some Error',
        })    
        icon.addClass('fa-check').removeClass('fa-spinner fa-spin')
    }
}
$("#etablissement").select2();
$("#formation").select2();
$("#nature").select2();
$("#etablissement").on('change', async function (){
    const id_etab = $(this).val();
    table_gestion_preins.columns().search("");

    table_gestion_preins.columns(0).search(id_etab).draw();
    let response = ""
    if(id_etab != "") {
        const request = await axios.get('/api/formation/'+id_etab);
        response = request.data
    }
    $('#formation').html(response).select2();
})
$("#formation").on('change', async function (){
    const id_formation = $(this).val();
    table_gestion_preins.columns(2).search("").draw();
    table_gestion_preins.columns(1).search(id_formation).draw();
})
$("#nature").on('change', async function (){
    table_gestion_preins.columns(2).search($(this).val()).draw();
})


const load_etud_info = () => {
    if(id_preinscription){
        const icon = $("#frais_preinscription i");
         icon.removeClass('fa-money-bill-alt').addClass("fa-spinner fa-spin");
        axios.get('/preinscription/gestion/frais_preins_modals/'+id_preinscription)
        .then(success => {
            $('.modal-preins .etudiant_info').html(success.data);
            icon.removeClass("fa-spinner fa-spin").addClass('fa-money-bill-alt');
            // success.data
        })
        .catch(err => {
            console.log(err);
            icon.removeClass("fa-spinner fa-spin").addClass('fa-money-bill-alt');
        })
    }    
}

const load_frais_preins = () => {
    if(id_preinscription){
        // icon.addClass('fa-spinner fa-spin').removeClass('fa-money-bill-alt')
        axios.get('/preinscription/gestion/article_frais/'+id_preinscription)
        .then(success => {
            $('.modal-preins .article #frais').html(success.data.list).select2();
            $('.modal-preins #code-facture').html(success.data.codefacture);
            $('#frais_preinscription-modal').modal("show");
            // success.data
        })
        .catch(err => {
            console.log(err);
            icon.removeClass("fa-spinner fa-spin").addClass('fa-money-bill-alt');
        })
    }    
}
$('body').on('click','#frais_preinscription',function (e) {
    e.preventDefault();
    if(!id_preinscription){
        Toast.fire({
          icon: 'error',
          title: 'Veuillez selection une ligne!',
        })
        return;
    }
    load_etud_info();
    load_frais_preins();
});
$('body').on('change','.modal-preins .article #frais',function (e) {
    e.preventDefault();
    let frais = $(this).find(':selected').attr('data-id');
    $('.modal-preins .article #montant').val(frais);
});
$('input[type=radio][name=organ]').on('change', async function (e){
    e.preventDefault();
    if (this.value == 0) {
        const request = await axios.get('/api/getorganismepasPayant');
        response = request.data
        $('.select-organ #org').html(response).select2();
        $('.select-organ').css('display','block');
    }else{
        $('.select-organ #org').html("");
        $('.select-organ').css('display','none');
    }
})
$('body').on('click','.modal #add-btn',function () {
    let fraisId  = $('.modal-preins .article #frais').val();
    let fraisText  = $('.modal-preins .article #frais').find(':selected').text();
    let prix  = $('.modal-preins .article #montant').val();
    let organ  = $('.select-organ #org').find(':selected').text();
    let organisme_id  = $('.select-organ #org').val();
    // console.log(fraisId)
    if (!$.isNumeric(fraisId) || prix == "") {
        return
    }
    if ($("input[name='organ']:checked").val() == 1) {
        organisme_id = 7
        organ = "Payant"
    }else if(organisme_id == ""){
        return
    }
    frais.push({
        index : Math.floor((Math.random() * 1000) + 1),
        id: fraisId ,
        designation: fraisText,
        montant: prix,
        organisme_id: organisme_id,
        organisme: organ
    });
    rawFrais();
})
    const rawFrais = () => {
        let html = "";
        frais.map((f, i) => {
            html += `
            <tr>
                <td>${i + 1}</td>
                <td>${f.designation}</td>
                <td>${f.montant}</td>
                <td>${f.organisme}</td>
                <td><button class='delete_frais btn btn-danger' id='${f.index}'><i class='fa fa-trash'></i></button></td>
            </tr>
        `
        })
        $(".modal-preins .table-fee tbody").html(html)
    }
    $("body").on("click", '.delete_frais', function () {
        const index = frais.findIndex(frais => frais.index == $(this).attr("id"));
        frais.splice(index,1);
        rawFrais();
    })

    $("body").on("click", '.modal .save', async function (e) {
        e.preventDefault();
        if(frais.length < 1){
            Toast.fire({
            icon: 'error',
            title: 'Veuillez Ajouter Des Frais!',
            })
            return;
        }
        console.log(frais)
        // return
        const icon = $(".modal .save i");
        icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
        var formData = new FormData();
        formData.append('frais', JSON.stringify(frais));
        try {
            const request = await axios.post("/preinscription/gestion/addfrais/"+id_preinscription, formData);
            const data = await request.data;
            $("#frais_preinscription-modal .modal-body").prepend(
                `<div class="alert alert-success">
                    <p>Bien Enregistre</p>
                </div>`
            );
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin");
            $(".modal-preins .table-fee tbody").empty();
            table_gestion_preins.ajax.reload(null,false);
            frais = [];
            window.open('/preinscription/gestion/facture/'+data, '_blank');
        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            $("#frais_preinscription-modal .modal-body").prepend(
                `<div class="alert alert-danger">${message}</div>`
            );
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin");
        }
        setTimeout(() => {
            $("#frais_preinscription-modal .modal-body .alert").remove();
        }, 3000);
    })

    $('body').on('click','#datables_gestion_preinscription tbody tr',function (e) {
        e.preventDefault();
        const input = $(this).find("input");
        if(input.is(":checked")){
            input.prop("checked",false);
            const index = idpreins.indexOf(input.attr("id"));
            idpreins.splice(index,1);
        }else{
            input.prop("checked",true);
            idpreins.push(input.attr("id"));
        }
        console.log(idpreins);
    })
    const getEtudiantInfos = async () => {
        $('#modifier_modal #candidats_infos').html('');
        $('#modifier_modal #parents_infos').html('');
        $('#modifier_modal #academique_infos').html('');
        $('#modifier_modal #divers').html('');
        const icon = $("#modifier i");
        icon.removeClass('fa-edit').addClass("fa-spinner fa-spin");
      try {
        const request = await axios.get('/preinscription/gestion/getEtudiantInfospreins/'+id_preinscription);
        const data = request.data;
        $('#modifier_modal #candidats_infos').html(data['candidats_infos']);
        $('#modifier_modal #parents_infos').html(data['parents_infos']);
        $('#modifier_modal #academique_infos').html(data['academique_infos']);
        $('#modifier_modal #divers').html(data['divers']);
        $('select').select2();
        icon.addClass('fa-edit').removeClass("fa-spinner fa-spin");
        // console.log(data);
  
      } catch (error) {
        // console.log(error.response.data);
      }  
    }
    $('body').on('dblclick','#datables_gestion_preinscription tbody tr',function (e) {
        e.preventDefault();
        // const input = $(this).find("input");
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            id_preinscription = null;
        } else {
            $("#datables_gestion_preinscription tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_preinscription = $(this).attr('id');
            getDocumentsPreins();
            // getEtudiantInfos();
        }
        console.log(id_preinscription);
    })

$("#annulation").on('click', async (e) => {
    e.preventDefault();
    if(idpreins.length < 1){
        Toast.fire({
          icon: 'error',
          title: 'Veuillez cocher une ou plusieurs ligne!',
        })
        return;
    }
    const icon = $("#annulation i");
    icon.removeClass('fa-times-circle').addClass("fa-spinner fa-spin");
    var formData = new FormData();
    formData.append('idpreins', JSON.stringify(idpreins));
    try {
        const request = await axios.post("/preinscription/gestion/annulation_preinscription", formData);
        const data = await request.data;
        Toast.fire({
            icon: 'success',
            title: 'Preinscription Bien Annuler',
        })
        idpreins = []
        icon.addClass('fa-times-circle').removeClass("fa-spinner fa-spin");
        table_gestion_preins.ajax.reload(null,false);
    } catch (error) {
        const message = error.response.data;
        console.log(error, error.response);
        Toast.fire({
            icon: 'error',
            title: 'Some Error',
        })
    }
})
$("#admission").on('click', async (e) => {
    e.preventDefault();
    if(idpreins.length < 1){
        Toast.fire({
          icon: 'error',
          title: 'Veuillez cocher une ou plusieurs ligne!',
        })
        return;
    }
    const icon = $("#admission i");
    icon.removeClass('fa-check').addClass("fa-spinner fa-spin");
    
    var formData = new FormData();
    formData.append('idpreins', JSON.stringify(idpreins));
    try {
        const request = await axios.post("/preinscription/gestion/admission_preinscription", formData);
        const data = await request.data;
        Toast.fire({
            icon: 'success',
            title: 'Admissions Bien Enregister',
        })
        icon.addClass('fa-check').removeClass("fa-spinner fa-spin");

        table_gestion_preins.ajax.reload(null,false);
      } catch (error) {
        const message = error.response.data;
        console.log(error, error.response);
        Toast.fire({
            icon: 'error',
            title: 'Some Error',
        })
        icon.addClass('fa-check').removeClass("fa-spinner fa-spin");

      }
})
$("#doc_preinscription").on('click', () => {
    if(!id_preinscription){
      Toast.fire({
        icon: 'error',
        title: 'Veuillez selection une ligne!',
      })
      return;
    }
    $('#document_preins_modal').modal("show");

})
$("body").on("click", ".ms-elem-selectable", async function() {
    $('.ms-selection .ms-list').prepend($(this).clone().removeClass("ms-elem-selectable").addClass("ms-elem-selection"))
    var formData = new FormData();
    formData.append('idDocument', $(this).attr("id"))
    formData.append('idPreinscription', id_preinscription);
    $(this).remove();
    try {
        const request = await axios.post("/preinscription/gestion/adddocuments_preins", formData);
        const data = await request.data;
    } catch (error) {
        Toast.fire({
            icon: 'error',
            title: 'error',
        })
    }
})
$("body").on("click", ".ms-elem-selection", async function() {
    $('.ms-selectable .ms-list').prepend($(this).clone().removeClass("ms-elem-selection").addClass("ms-elem-selectable"))
    var formData = new FormData();
    formData.append('idDocument', $(this).attr("id"))
    formData.append('idPreinscription', id_preinscription);
    $(this).remove();
    try {
        const request = await axios.post("/preinscription/gestion/deletedocuments_preins", formData);
        const data = await request.data;
        
    } catch (error) {
        Toast.fire({
            icon: 'error',
            title: 'error',
        })
    }
})

$('body').on('click','#att_preinscription',function () {
    if(!id_preinscription){
        Toast.fire({
            icon: 'error',
            title: 'Veuillez selection une ligne!',
        })
        return;
    }
    window.open('/preinscription/gestion/attestation_preinscription/'+id_preinscription, '_blank');
})

$('body').on('click','#cfc_preinscription',function () {
    if(!id_preinscription){
        Toast.fire({
            icon: 'error',
            title: 'Veuillez selection une ligne!',
        })
        return;
    }
    window.open('/preinscription/gestion/cfc_preinscription/'+id_preinscription, '_blank');
})

$('body').on('click','#modifier',function () {
    if(!id_preinscription){
        Toast.fire({
            icon: 'error',
            title: 'Veuillez selection une ligne!',
        })
        return;
    }
    getEtudiantInfos();
    $('#modifier_modal').modal("show");
})

$("body").on('submit', "#form_modifier", async (e) => {
    e.preventDefault();
    // alert('et');
    if(!id_preinscription){
        Toast.fire({
          icon: 'error',
          title: 'Merci de Choisir Un Etudiant!',
        })
        return;
    }
    var res = confirm('Vous voulez vraiment modifier cette enregistrement ?');
    if(res == 1){
      var formData = new FormData($('#form_modifier')[0]);
    //   console.log(formData);
      let modalAlert = $("#modifier_modal .modal-body .alert")
      modalAlert.remove();
      const icon = $("#modifier_modal button i");
      icon.removeClass('fa-edit').addClass("fa-spinner fa-spin");
      try {
        const request = await axios.post('/preinscription/gestion/edit_infos_preins/'+id_preinscription, formData);
        const response = request.data;
        $("#modifier_modal .modal-body").prepend(
          `<div class="alert alert-success" style="width: 98%;margin: 0 auto;">
              <p>${response}</p>
            </div>`
        );
        icon.addClass('fa-edit').removeClass("fa-spinner fa-spin ");
        id_preinscription = false;
        table_gestion_preins.ajax.reload(null, false)
      }catch (error) {
        // console.log(error, error.response);
        const message = error.response.data;
        modalAlert.remove();
        $("#modifier_modal .modal-body").prepend(
          `<div class="alert alert-danger" style="width: 98%;margin: 0 auto;">${message}</div>`
        );
        icon.addClass('fa-edit').removeClass("fa-spinner fa-spin ");
      }
      setTimeout(() => {
        $(".modal-body .alert").remove();
        // modalAlert.remove();
      }, 2500)  
    }
  })
  
  $('body').on('click','#extraction', function (){
        window.open('/preinscription/gestion/extraction_preins', '_blank');
  })
  $('body').on('click','#imprimer_docs', function (){
    if(!id_preinscription){
        Toast.fire({
          icon: 'error',
          title: 'Merci de Choisir Un Etudiant!',
        })
        return;
    }
    window.open('/preinscription/gestion/print_documents_preinscription/'+id_preinscription, '_blank');
  })
  
$('.nav-pills a').on('click', function (e) {
    $(this).tab('show');
})

})

