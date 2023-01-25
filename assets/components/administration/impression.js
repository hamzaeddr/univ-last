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

let check = 0;
let inscription_id = null;
    
$(document).ready(function  () {
    // $("#valider, #import, #simuler").attr('disabled', true)
    const enableButtons = () => {
        if(check == 0) {
            // $("#valider").removeClass('btn-secondary').addClass('btn-danger').attr('disabled', false)
            // $("#import").addClass('btn-secondary').removeClass('btn-success').attr('disabled', true)
            $("#imprimer").addClass('btn-secondary').removeClass('btn-info').attr('disabled', true)
        } else {
            // $("#valider").addClass('btn-secondary').removeClass('btn-danger').attr('disabled', true)
            // $("#import").removeClass('btn-secondary').addClass('btn-success').attr('disabled', false)
            $("#imprimer").removeClass('btn-secondary').addClass('btn-info').attr('disabled', false)
        }
    }
    enableButtons();
    $("#etablissement").select2();
    $("#order").select2();
    $("#etablissement").on('change', async function (){
        const id_etab = $(this).val();
        let response = ""
        if(id_etab != "") {
            const request = await axios.get('/api/formation/'+id_etab);
            response = request.data
        }
        $('#formation').html(response).select2();
    })
    $("#formation").on('change', async function (){
        const id_formation = $(this).val();
        let response = ""
        if(id_formation != "") {
            const request = await axios.get('/api/promotion/'+id_formation);
            response = request.data
        }
        $('#promotion').html(response).select2();
    })
    $("#promotion").on('change', async function (){
        const id_promotion = $(this).val();
        let response = ""
        if(id_promotion != "") {
            const request = await axios.get('/api/salle/'+id_promotion);
            response = request.data
        }
        $('#salle').html(response).select2();
    })
    

    $("#get_list_etudiant").on('click', async function(e){
        e.preventDefault();
        $("#list_etudiants").empty()
        let promotion_id = $('#promotion').val();
        if(promotion_id == "" || !promotion_id) {
            Toast.fire({
                icon: 'error',
                title: 'Veuillez selection promotion!',
            })
            return;
        }
        let salle = $('#salle').val();
        if(salle == "" || !salle) {
            Toast.fire({
                icon: 'error',
                title: 'Veuillez selection une salle!',
            })
            return;
        }
        const icon = $("#get_list_etudiant i");
        icon.removeClass('fa-search').addClass("fa-spinner fa-spin");
        try {
            let formData = new FormData();
            formData.append("order", $("#order").val())
            const request = await axios.post('/administration/impression/list/'+promotion_id+"/"+salle, formData);
            let response = request.data
            // $("#list_epreuve_normal").DataTable().destroy()
            if ($.fn.DataTable.isDataTable("#list_etudiants")) {
                $('#list_etudiants').DataTable().clear().destroy();
              }
            $("#list_etudiants").html(response).DataTable({
                scrollX: true,
                scrollY: true,
                language: {
                    url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
                },
            });
            check = 1;
            enableButtons();
            icon.addClass('fa-search').removeClass("fa-spinner fa-spin");
        } catch (error) {
            console.log(error)
            icon.addClass('fa-search').removeClass("fa-spinner fa-spin");
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
        }

    })
    

    $("#import").on('click', async function (e){
        e.preventDefault();
        $('#import_en_masse').modal("show");
    })
    $("#impression_canvas").on('click', function () {
        window.open("/administration/impression/canvas", '_blank');
    })
    $("#import_impression_save").on("submit", async function(e) {
        e.preventDefault();
        let formData = new FormData($(this)[0]);
        let modalAlert = $("#import_en_masse .modal-body .alert")
    
        modalAlert.remove();
        const icon = $("#impression_enregistre i");
        icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
        
        try {
          const request = await axios.post('/administration/impression/import', formData);
          const response = request.data;
          $("#import_en_masse .modal-body").prepend(
            `<div class="alert alert-success">
                <p>${response}</p>
              </div>`
          );
          icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
          $("#get_list_etudiant").trigger("click")

        } catch (error) {
          const message = error.response.data;
          console.log(error, error.response);
          modalAlert.remove();
          $("#import_en_masse .modal-body").prepend(
            `<div class="alert alert-danger">${message}</div>`
          );
          icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
          
        }
    })
    $("#imprimer").on('click', function () {
        window.open("/administration/impression/imprimer", '_blank');
    })
})


    


