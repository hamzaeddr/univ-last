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
let id_etudiant = false;
// $('select').select2();
$(document).ready(function  () {

  var table = $("#datables_etudiant").DataTable({
    lengthMenu: [
      [10, 15, 25, 50, 100, 20000000000000],
      [10, 15, 25, 50, 100, "All"],
    ],
    order: [[0, "desc"]],
    ajax: "/etudiant/etudiants/list",
    processing: true,
    serverSide: true,
    deferRender: true,
    responsive: true,
    scrollX: true,
    drawCallback: function () {
      if(id_etudiant) {
        $("body tr#" + id_etudiant).addClass('active_databales');
      }
    },
    preDrawCallback: function(settings) {
        if ($.fn.DataTable.isDataTable('#datables_etudiant')) {
            var dt = $('#datables_etudiant').DataTable();

            //Abort previous ajax request if it is still in process.
            var settings = dt.settings();
            if (settings[0].jqXHR) {
                settings[0].jqXHR.abort();
            }
        }
    },
    language: {
      url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
    },
  });

  const getAppelRdv = async () => {
    $('#rdv1').val("");
    $('#rdv2').val("");
      const icon = $("#date-d-appel i");
      icon.removeClass('fa-edit').addClass("fa-spinner fa-spin");
  try {
    const request = await axios.get('/etudiant/etudiants/getAppelRdv/'+id_etudiant);
    const data = request.data;
    $('#rdv1').val(data['rdv1']);
    $('#rdv2').val(data['rdv2']);
      icon.addClass('fa-edit').removeClass("fa-spinner fa-spin");
    // console.log(data);

  } catch (error) {
    // console.log(error.response.data);
  }  
}

  const getEtudiantInfos = async () => {
      $('#modifier_modal #candidats_infos').html('');
      $('#modifier_modal #parents_infos').html('');
      $('#modifier_modal #academique_infos').html('');
      $('#modifier_modal #divers').html('');
      const icon = $("#modifier i");
      icon.removeClass('fa-edit').addClass("fa-spinner fa-spin");
    try {
      const request = await axios.get('/etudiant/etudiants/getEtudiantInfos/'+id_etudiant);
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

  const getEtablissement = async () => {
    try {
      const request = await axios.get('/api/etablissement');
      const data = request.data;
      $('#etablissement').html(data).select2();

    } catch (error) {
      // console.log(error.response.data);
    }  
  }
  // const getNatureDemande = async () => {
  //   try {
  //     const request = await axios.get('/api/nature_demande');
  //     const data = request.data;
  //     $('#naturedemande').html(data).select2();

  //   } catch (error) {
  //     // console.log(error.response.data);
  //   }  
  // }
  getEtablissement();
  // getNatureDemande();

  const loadExistMatieres = () => {
    $(".matiereExist tbody").html('<i class="fas fa-spinner fa-spin"></i>')
    axios.get('/etudiant/etudiants/matiere/'+id_etudiant)
      .then(success => {
        $(".matiereExist tbody").html(success.data.table)
        $("#matiereDispo").html(success.data.matieres).select2();
        // console.log(success)
      })
      .catch(err => {
        // console.log(err)
      })
  }
  const loadEtudiantStatut = () => {
    axios.get('/etudiant/etudiants/statut/'+id_etudiant)
      .then(success => {
        $("#statut").html(success.data)
      })
      .catch(err => {
        // console.log(err)
      })
  }
  
  let tableListPreinscription;

  $('body').on('click','#datables_etudiant tbody tr',function () {
    if($(this).hasClass('active_databales')) {
      id_etudiant = null,
      $('#datables_etudiant tr').removeClass('active_databales');
      return;
    }
    $('#datables_etudiant tr').removeClass('active_databales');
    $(this).addClass('active_databales');
    id_etudiant = $(this).attr('id');
    tableListPreinscription = $("#datables_etudiant_modal").DataTable({
      lengthMenu: [
        [10, 15, 25, 50, 100, 20000000000000],
        [10, 15, 25, 50, 100, "All"],
      ],
      order: [[0, "desc"]],
      ajax: "/etudiant/etudiants/list/preinscription/"+id_etudiant,
      processing: true,
      serverSide: true,
      deferRender: true,
      language: {
        url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
      },
      stateSave: true,
      bDestroy: true
    });    
    loadExistMatieres();
    loadEtudiantStatut();
    // getEtudiantInfos();
    // getAppelRdv()
  })
  
  let cancelToken;
  $('body').on('change','#etablissement',function () {
    let id_etab = $(this).val();
    if (typeof cancelToken != typeof undefined) {
        cancelToken.cancel("Operation canceled due to new request.")
    }
    
      //Save the cancel token for the current request
    cancelToken = axios.CancelToken.source()
    axios.get('/api/formation/'+id_etab, { cancelToken: cancelToken.token })
    .then(success => {
      $('.formation').css('display','block');
      $('#formation').html(success.data).select2();
    })
  })

  $('body').on('change','#formation',function (e) {
    e.preventDefault();
    let id_forma = $(this).val();
    axios.get('/api/anneeresidanat/'+id_forma)
    .then(success => {
      if(success.data !== 1){
        $('.annee').css('display','block');
        $('#annee').html(success.data).select2();
      }else{
        $('.annee').css('display','none');
      }
    })
    $('#enregistrer').removeAttr("disabled");
  })

  $('body').on('change','#annee',function (e) {
    e.preventDefault();
    $('#enregistrer').removeAttr("disabled");
  })
  
  $("#valider-modal").on('click', () => {
    // console.log(id_etudiant);
    if(!id_etudiant){
      Toast.fire({
        icon: 'error',
        title: 'Veuillez selection une ligne!',
      })
      return;
    }
    $("#validermodal .modal-body #annee,#validermodal .modal-body #formation").empty();
    $('#validermodal').modal("show")
  })
  $('body').on('submit','.form-valider',async function (e) {
    e.preventDefault();
    // alert('test');
    let formdata = $(this).serialize();
    let modalAlert =  $("#validermodal .modal-body .alert");
    modalAlert.remove();
    const icon = $(".form-valider .btn i");
    icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
    try{
      const request = await  axios.post('/etudiant/etudiants/etudiant_valider/'+id_etudiant,formdata)
      const data = request.data;
      if (data === 1) {
        $("#validermodal .modal-body").prepend(
          `<div class="alert alert-danger">Etudiant déja inscrit dans la meme formation</div>`
        );
        icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin");
      }else{
        $("#validermodal .modal-body").prepend(
          `<div class="alert alert-success">${data}</div>`
        );
        // modalAlert.prepend(
        //   `<div class="alert alert-success">
        //       <p>${data}</p>
        //     </div>`
        // );  
        icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin");
        tableListPreinscription.ajax.reload(null, false);
        table.ajax.reload(null, false);
      }
    }catch(error){
      const message = error.response.data;
      // console.log(error, error.response);
      modalAlert.remove();
      $("#validermodal .modal-body").prepend(
        `<div class="alert alert-danger">${message}</div>`
      );
      icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
    } 
    setTimeout(() => {
      $(".modal-body .alert").remove();
    }, 2000)  
  })

  $('#releve_note').on('click', () => {
    if(!id_etudiant){
      Toast.fire({
        icon: 'error',
        title: 'Veuillez selection une ligne!',
      })
      return;
    }
    $("#releves-notes-modal").modal("show");
  })

  $("body").on('submit', '#relevenote_save', async (e) => {
    e.preventDefault();
    if($("#matiereDispo").val() == "" || $("#matiereNote").val() == "") {
      $(".modal-body").prepend(
        `<div class="alert alert-danger">Veuillez remplir tout les champs</div>`
      );
      return;
    }
    let formdata = new FormData();
    let modalAlert =  $("#releves-notes-modal .modal-body .alert");
    formdata.append('matiere', $("#matiereDispo").val())
    formdata.append('note', $("#matiereNote").val())
    // console.log(formdata);
    $(".modal-body .alert").remove();
    const icon = $("#relevenote_save .btn i");
    icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
    try{
      const request = await  axios.post('/etudiant/etudiants/addmatiere/'+id_etudiant,formdata)
      const data = request.data;
      modalAlert.prepend(
        `<div class="alert alert-success">
            <p>${data}</p>
          </div>`
      );  
      icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin");
      loadExistMatieres();
       
    }catch(error){
      const message = error.response.data;
      // console.log(error, error.response);
      modalAlert.remove();
      $("#releves-notes-modal .modal-body").prepend(
        `<div class="alert alert-danger">${message}</div>`
      );
      icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
    }
    setTimeout(() => {
      $(".modal-body .alert").remove();
    }, 2000)

  })
  $("body").on('click', '.delete_matiere', function() {
    let id = $(this).attr("id");
    $(this).removeClass("fa-trash").addClass("fa-spinner fa-spin");
    try {
      const request = axios.post("/etudiant/etudiants/matiere/delete/"+id)
      const data = request.data;
      
      loadExistMatieres();
      
    } catch (error) {
      // console.log(error.response.data)
    }
  })

  $('#etudiant_import').on('click', () => {
    $("#importer-modal").modal("show");
  })
  $('#save_import').on('submit', async (e) => {
    e.preventDefault();
    let modalAlert = $("#importer-modal .modal-body .alert")
    modalAlert.remove();
    const icon = $("#save_import .btn i");
    // const button = $("#import-group-ins .btn");
    icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
    var formData = new FormData();
    formData.append('file', $('.myfile').prop('files')[0]);
    // console.log(formData);
    try {
      const request = await axios.post("/etudiant/etudiants/import", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });
      const data = await request.data;
      $("#importer-modal .modal-body").prepend(
        `<div class="alert alert-success">
            <p>Nombre d'insertion:<b>${data.inserted}</b></p>
            <p<b>${data.existed}</b> étudiants exist</p>
          </div>`
      );
      // console.log(data.existed);
      if(data.existed > 0) {
        window.open("/etudiant/etudiants/download", '_blank');
      }
      icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin");
      table.ajax.reload(null, false);
    } catch (error) {
      const message = error.response.data;
      // console.log(error, error.response);
      modalAlert.remove();
      $("#importer-modal .modal-body").prepend(
        `<div class="alert alert-danger">${message}</div>`
      );
      icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
    }
    setTimeout(() => {
      $(".modal-body .alert").remove();
    }, 2500) 
  
    // $("#save_import")[0].reset();
  });


  $("#date-d-appel").on("click", () => {
    if(!id_etudiant){
      Toast.fire({
        icon: 'error',
        title: 'Veuillez selection une ligne!',
      })
      return;
    }
    $("#date-d-appel-modal").modal("show")
  })

  $("body").on('submit', "#date_appele_save", async (e) => {
    e.preventDefault();
    let formData = new FormData($("#date_appele_save")[0]);
    let modalAlert = $("#date-d-appel-modal .modal-body .alert")

    modalAlert.remove();
    const icon = $("#date_appele_save .btn i");
    icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
    
    try {
      const request = await axios.post('/etudiant/etudiants/datedernierappel/'+id_etudiant, formData);
      const response = request.data;
      $("#date-d-appel-modal .modal-body").prepend(
        `<div class="alert alert-success">
            <p>${response}</p>
          </div>`
      );
      icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
      table.ajax.reload(null, false)
    } catch (error) {
      const message = error.response.data;
      // console.log(error, error.response);
      modalAlert.remove();
      $("#date-d-appel-modal .modal-body").prepend(
        `<div class="alert alert-danger">${message}</div>`
      );
      icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
    }
    setTimeout(() => {
      $(".modal-body .alert").remove();
    }, 2500) 
   
  })

  $("#etudiant_statut").on("click", () => {
    if(!id_etudiant){
      Toast.fire({
        icon: 'error',
        title: 'Veuillez selection une ligne!',
      })
      return;
    }
    $("#statut-modal").modal("show")
  })
  $("body").on('submit', "#change_statut_save", async (e) => {
    e.preventDefault();
    let formData = new FormData($("#change_statut_save")[0]);
    let modalAlert = $("#statut-modal .modal-body .alert")

    modalAlert.remove();
    const icon = $("#change_statut_save .btn i");
    icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
    try {
      const request = await axios.post('/etudiant/etudiants/statut/persist/'+id_etudiant, formData);
      const response = request.data;
      $("#statut-modal .modal-body").prepend(
        `<div class="alert alert-success">
            <p>${response}</p>
          </div>`
      );
      icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
      table.ajax.reload(null, false)
    } catch (error) {
      const message = error.response.data;
      // console.log(error, error.response);
      modalAlert.remove();
      $("#statut-modal .modal-body").prepend(
        `<div class="alert alert-danger">${message}</div>`
      );
      icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
    }
    setTimeout(() => {
      $(".modal-body .alert").remove();
    }, 2500) 
  })
  $('.nav-pills a').on('click', function (e) {
      $(this).tab('show');
  })
  $('body').on('click','#modifier',function () {
    if(!id_etudiant){
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
    if(!id_etudiant){
        Toast.fire({
          icon: 'error',
          title: 'Merci de Choisir Un Etudiant!',
        })
        return;
    }
    var res = confirm('Vous voulez vraiment modifier cette enregistrement ?');
    if(res == 1){
      var formData = new FormData($('#form_modifier')[0]);
      // console.log(formData);
      let modalAlert = $("#modifier_modal .modal-body .alert")
      modalAlert.remove();
      const icon = $("#modifier_modal button i");
      icon.removeClass('fa-edit').addClass("fa-spinner fa-spin");
      try {
        const request = await axios.post('/etudiant/etudiants/edit_infos/'+id_etudiant, formData);
        const response = request.data;
        $("#modifier_modal .modal-body").prepend(
          `<div class="alert alert-success" style="width: 98%;margin: 0 auto;">
              <p>${response}</p>
            </div>`
        );
        icon.addClass('fa-edit').removeClass("fa-spinner fa-spin ");
        table.ajax.reload(null, false)
      }catch (error) {
        const message = error.response.data;
        // console.log(error, error.response);
        modalAlert.remove();
        $("#modifier_modal .modal-body").prepend(
          `<div class="alert alert-danger" style="width: 98%;margin: 0 auto;">${message}</div>`
        );
        icon.addClass('fa-edit').removeClass("fa-spinner fa-spin ");
      }
    }
    setTimeout(() => {
      $(".modal-body .alert").remove();
    }, 2500)  
  })
  $("body").on('click', "#ajouter", (e) => {
    e.preventDefault();
    // $('#ajouter_modal #candidats_infos').html('');
    // $('#ajouter_modal #parents_infos').html('');
    // $('#ajouter_modal #academique_infos').html('');
    // $('#ajouter_modal #divers').html('');
    $('#ajouter_modal').modal("show");
    $('select').select2();
  })
  
  $("body").on('submit', "#form_ajouter", async (e) => {
    e.preventDefault();
    var res = confirm('Vous voulez vraiment ajouter cette enregistrement ?');
    if(res == 1){
      var formData = new FormData($('#form_ajouter')[0]);
      // console.log(formData);
      let modalAlert = $("#ajouter_modal .modal-body .alert")
      modalAlert.remove();
      const icon = $("#ajouter_modal button i");
      icon.removeClass('fa-plus').addClass("fa-spinner fa-spin");
      try {
        const request = await axios.post('/etudiant/etudiants/add_infos', formData);
        const response = request.data;
        $("#ajouter_modal .modal-body").prepend(
          `<div class="alert alert-success" style="width: 98%;margin: 0 auto;">
              <p>${response}</p>
            </div>`
        );
        icon.addClass('fa-plus').removeClass("fa-spinner fa-spin ");
        table.ajax.reload(null, false)
      }catch (error) {
        const message = error.response.data;
        // console.log(error, error.response);
        modalAlert.remove();
        $("#ajouter_modal .modal-body").prepend(
          `<div class="alert alert-danger" style="width: 98%;margin: 0 auto;">${message}</div>`
        );
        icon.addClass('fa-plus').removeClass("fa-spinner fa-spin ");
      }
    }
    setTimeout(() => {
      $(".modal-body .alert").remove();
    }, 2500)  
  })
})