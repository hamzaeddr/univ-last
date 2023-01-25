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
    let id_admission = false;
    let idAdmissions = [];
    let frais = [];
    
    $(document).ready(function  () {
    var table = $("#datatables_gestion_admission").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/admission/gestion/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        responsive: true,
        scrollX: true,
        drawCallback: function () {
            idAdmissions.forEach((e) => {
                $("body tr#" + e)
                .find("input")
                .prop("checked", true);
            });
            $("body tr#" + id_admission).addClass('active_databales')
        },
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
    const getDocuments = async () => {
        try {
            const icon = $('#document i')
            icon.removeClass('fa-check').addClass('fa-spinner fa-spin')
            const request = await axios.get("/admission/gestion/getdocuments/"+id_admission);
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
    const getOrganisme = async () => {
        try {
            const request = await axios.get("/api/organisme");
            const data = await request.data;
            $('#organisme').html(data).select2();
          } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            Toast.fire({
                icon: 'error',
                title: 'Some Error',
            })    
        }
    }
    const getNatureEtudiant = async () =>{
        try {
            const request = await axios.get("/api/nature_etudiant/"+id_admission);
            const data = await request.data;
            $('#organisme').html(data).select2();
          } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            Toast.fire({
                icon: 'error',
                title: 'Some Error',
            })    
        }
    }
    $("#frais").on("change", () => {
        $("#montant").val($("#frais").find(":selected").data('frais'))
    })
    getOrganisme();
    $("#etablissement").select2()
    $("#etablissement").on('change', async function (){
        const id_etab = $(this).val();
        table.columns().search("");
        table.columns(0).search(id_etab).draw();
        let response = ""
        if(id_etab != "") {
            const request = await axios.get('/api/formation/'+id_etab);
            response = request.data
        } else {
            
            $('#annee').html("").select2();
        }
        $('#formation').html(response).select2();
    })
    $("#formation").on('change', async function (){
        const id_formation = $(this).val();
        table.columns().search("");
        table.columns(1).search(id_formation).draw();
        let response = ""
        if(id_formation != "") {
            const request = await axios.get('/api/annee/'+id_formation);
            response = request.data
        }
        $('#annee').html(response).select2();
    })
    $("#annee").on('change', async function (){
        table.columns().search("");
        table.columns(2).search($(this).val()).draw();
    })
    const getInscriptionAnnee = async () => {
        const icon = $('#inscription-modal i')
        try {
            icon.removeClass('fa-check').addClass('fa-spinner fa-spin')
            const request = await axios.get("/admission/gestion/getAnneeDisponible/"+id_admission);
            const data = await request.data;
            $('#annee_inscription').html(data.anneeHtml).select2();
            $('#promotion_inscription').html(data.promotionHtml).select2();
            $('#inscription-modal').attr('disabled', false);
        } catch (error) {
            $('#inscription-modal').attr('disabled', true);
            $('#annee_inscription, #promotion_inscription').empty()
            const message = error.response.data;
            console.log(error, error.response);
            Toast.fire({
                icon: 'info',
                title: message,
            })    
        }
        icon.addClass('fa-check').removeClass('fa-spinner fa-spin')
    }
    
    $('body').on('click','#datatables_gestion_admission tbody tr',function () {
        const input = $(this).find("input");
        if(input.is(":checked")){
            input.prop("checked",false);
            const index = idAdmissions.indexOf(input.attr("id"));
            idAdmissions.splice(index,1);
        }else{
            input.prop("checked",true);
            idAdmissions.push(input.attr("id"));
        }
    })
    $('body').on('dblclick','#datatables_gestion_admission tbody tr',function () {
        // const input = $(this).find("input");
        
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            $('#inscription-modal').attr('disabled', true);
            id_admission = null;
        } else {
            $("#datatables_gestion_admission tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_admission = $(this).attr('id');
            getNatureEtudiant();
            getInscriptionAnnee();
            getDocuments();          
        }
        
    })
    
    $("#document").on("click", () => {
        if(!id_admission){
          Toast.fire({
            icon: 'error',
            title: 'Veuillez selection une ligne!',
          })
          return;
        }
  
        $("#document_modal").modal("show")
    })
    $("body").on("click", ".ms-elem-selection", async function() {
        $('.ms-selectable .ms-list').prepend($(this).clone().removeClass("ms-elem-selection").addClass("ms-elem-selectable"))
        var formData = new FormData();
        formData.append('idDocument', $(this).attr("id"))
        formData.append('idAdmission', id_admission);
        $(this).remove();
        try {
            const request = await axios.post("/admission/gestion/deletedocument", formData);
            const data = await request.data;
            
        } catch (error) {
            Toast.fire({
                icon: 'error',
                title: 'error',
            })
        }
    })
    $("body").on("click", ".ms-elem-selectable", async function() {
        $('.ms-selection .ms-list').prepend($(this).clone().removeClass("ms-elem-selectable").addClass("ms-elem-selection"))
        var formData = new FormData();
        formData.append('idDocument', $(this).attr("id"))
        formData.append('idAdmission', id_admission);
        $(this).remove();
        try {
            const request = await axios.post("/admission/gestion/adddocuments", formData);
            const data = await request.data;
        } catch (error) {
            Toast.fire({
                icon: 'error',
                title: 'error',
            })
        }
    })
    const getFrais = async () => {
        try {
            const request = await axios.get("/api/frais/"+id_admission);
            const data = await request.data;
            $('#frais').html(data.list).select2();
            $('#code-facture').html(data.codefacture);
            $("#frais_inscription-modal").modal("show")

          } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            Toast.fire({
                icon: 'error',
                title: 'Some Error',
            })
        }
    }
    const getAdmissionInfos = async () => {
        try {
            const icon = $('#frais-modal i')
            icon.removeClass('fa-money-bill-alt').addClass('fa-spinner fa-spin')
            const request = await axios.get("/admission/gestion/info/"+id_admission);
            const data = await request.data;
            $('.etudiant_info').html(data);
            icon.addClass('fa-money-bill-alt').removeClass('fa-spinner fa-spin')
        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            Toast.fire({
                icon: 'error',
                title: 'Some Error',
            })
            icon.addClass('fa-money-bill-alt').removeClass('fa-spinner fa-spin')    
        }
    }
    $("#frais-modal").on("click", () => {
        if(!id_admission){
          Toast.fire({
            icon: 'error',
            title: 'Veuillez selection une ligne!',
          })
          return;
        }
        getAdmissionInfos(); 
        getFrais();
    })
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
    $("#add_frais_gestion").on("click", () => {

        let fraisId = $("#frais").find(":selected").val();
        if(fraisId != "") {
            let fraisText = $("#frais").find(":selected").text();
            let prix = $("#montant").val();
            let ice = $("#ice").val();
            let organ  = $('.select-organ #org').find(':selected').text();
            let organisme_id  = $('.select-organ #org').val();
            if (!$.isNumeric(fraisId) || prix == "") {
                return
            }
            if ($("input[name='organ']:checked").val() == 1) {
                organisme_id = 7
                organ = "Payant"
            }else if(organisme_id == ""){
                return
            }
            console.log($("input[name='organ']:checked").val());
                frais.push({
                    index : Math.floor((Math.random() * 1000) + 1),
                    id: fraisId,
                    designation: fraisText,
                    montant: prix,
                    ice: ice,
                    organisme_id: organisme_id,
                    organisme: organ
                });
                rawFrais();
        }
    })
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
                <td><button class='delete_frais btn btn-danger'  id='${f.index}'><i class='fa fa-trash' ></i></button></td>
            </tr>
        `
        })
        // console.log(html);
        $(".table_frais_admission").html(html)
    }
    $("body").on("click", '.delete_frais', function () {
        const index = frais.findIndex(frais => frais.index == $(this).attr("id"));
        frais.splice(index,1);
        rawFrais();
    })
    $("#save_frais_gestion").on("click", async () => {
        let formData = new FormData();
        formData.append("frais", JSON.stringify(frais))
        // formData.append("organisme", $("#organisme").val())
        let modalAlert = $("#frais_inscription-modal .modal-body .alert")
    
        modalAlert.remove();
        const icon = $("#save_frais_gestion i");
        icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
        
        try {
          const request = await axios.post('/admission/gestion/addfrais/'+id_admission, formData);
          const response = request.data;
          $("#frais_inscription-modal .modal-body").prepend(
            `<div class="alert alert-success">
                <p>Bien Enregistre</p>
              </div>`
          );
          icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
          $(".table_frais_admission").empty()
          frais = [];
          window.open("/admission/gestion/facture/"+response, '_blank');
          table.ajax.reload(null, false);
        } catch (error) {
          const message = error.response.data;
          console.log(error, error.response);
          modalAlert.remove();
          $("#frais_inscription-modal .modal-body").prepend(
            `<div class="alert alert-danger">${message}</div>`
          );
          icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
        }
        setTimeout(() => {
            $("#frais_inscription-modal .modal-body .alert").remove();
        }, 3000);
    })

    $("#inscription-modal").on("click", () => {
        if(!id_admission){
          Toast.fire({
            icon: 'error',
            title: 'Veuillez selection une ligne!',
          })
          return;
        }
        $("#inscription_modal .modal-body .alert").remove()
        $("#inscription_modal").modal("show")
    })

    $("#inscription_save").on("submit", async function (e){
        e.preventDefault();
        let formData = new FormData($(this)[0]);
        let modalAlert = $("#inscription_modal .modal-body .alert")
    
        modalAlert.remove();
        const icon = $("#inscription_save .btn i");
        icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
        
        try {
          const request = await axios.post('/admission/gestion/inscription/'+id_admission, formData);
          const response = request.data;
          $("#inscription_modal .modal-body").prepend(
            `<div class="alert alert-success">
                <p>${response}</p>
              </div>`
          );
          icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
          $("#annee_inscription, #promotion_inscription, #organisme").empty()
          table.ajax.reload(null, false)
        } catch (error) {
          const message = error.response.data;
          console.log(error, error.response);
          modalAlert.remove();
          $("#inscription_modal .modal-body").prepend(
            `<div class="alert alert-danger">${message}</div>`
          );
          icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
        }
    })

    $("#attestation_admission").on('click', function(){
        if(!id_admission) {
            Toast.fire({
                icon: 'error',
                title: 'Veuillez selection une ligne!',
            })
            return;
        }
        window.open("/admission/gestion/attestation/"+id_admission, '_blank');
    })
    $('body').on('click','#imprimer_docs', function (){
      if(!id_admission){
          Toast.fire({
            icon: 'error',
            title: 'Merci de Choisir Une ligne!',
          })
          return;
      }
      window.open('/admission/gestion/print_documents_admission/'+id_admission, '_blank');
    })
})
    
    