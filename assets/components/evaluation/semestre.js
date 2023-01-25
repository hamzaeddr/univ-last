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

let check;
    
$(document).ready(function  () {
    $("#enregister, #valider, #devalider, #recalculer, #imprimer, #statut").attr('disabled', true)
    const enableButtons = () => {
        $("#imprimer").removeClass('btn-secondary').addClass('btn-info').attr('disabled', false)
        $("#statut").removeClass('btn-secondary').addClass('btn-primary').attr('disabled', false)

        if(check == 0) {
            $("#enregister").removeClass('btn-secondary').addClass('btn-primary').attr('disabled', false)
            $("#valider").removeClass('btn-secondary').addClass('btn-danger').attr('disabled', false)
            $("#devalider").addClass('btn-secondary').removeClass('btn-success').attr('disabled', true)
            $("#recalculer").addClass('btn-secondary').removeClass('btn-warning').attr('disabled', true)
        } else {
            $("#enregister").addClass('btn-secondary').removeClass('btn-primary').attr('disabled', true)
            $("#valider").addClass('btn-secondary').removeClass('btn-danger').attr('disabled', true)
            $("#devalider").removeClass('btn-secondary').addClass('btn-success').attr('disabled', false)
            $("#recalculer").removeClass('btn-secondary').addClass('btn-warning').attr('disabled', false)
        }
    }
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
            const request = await axios.get('/api/semestre/'+id_promotion);
            response = request.data
        }
        $('#semestre').html(response).select2();
    })
    

    $("#get_list_etudiant").on('click', async function(e){
        e.preventDefault();
        $("#list_epreuve_normal").empty()
        let semestre_id = $('#semestre').val();
        if(semestre_id == "" || !semestre_id) {
            Toast.fire({
                icon: 'error',
                title: 'Veuillez selection semestre!',
            })
            return;
        }
        const icon = $("#get_list_etudiant i");
        icon.removeClass('fa-search').addClass("fa-spinner fa-spin");
        try {
            let formData = new FormData();
            formData.append("order", $("#order").val())
            const request = await axios.post('/evaluation/semestre/list/'+semestre_id, formData);
            let response = request.data
            // $("#list_epreuve_normal").DataTable().destroy()
            if ($.fn.DataTable.isDataTable("#list_epreuve_normal")) {
                $('#list_epreuve_normal').DataTable().clear().destroy();
              }
            $("#list_epreuve_normal").html(response.html).DataTable({
                scrollX: true,
                scrollY: true,
                language: {
                    url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
                },
            });
            check = response.check;
            if(check == 1){
                Toast.fire({
                    icon: 'info',
                    title: "Operation déja valider",
                }) 
            }
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
    $("#imprimer").on("click", () => {  
        $("#imprimer_list").modal("show")
    })

    $("#valider").on('click', async function(){
        const icon = $("#valider i");
        icon.removeClass('fa-lock').addClass("fa-spinner fa-spin");
        try {
            const request = await axios.post('/evaluation/semestre/valider');
            let response = request.data
            check = 1;
            enableButtons();
            Toast.fire({
                icon: 'success',
                title: response,
            });
            icon.addClass('fa-lock').removeClass("fa-spinner fa-spin");
        } catch (error) {
            console.log(error)
            icon.addClass('fa-lock').removeClass("fa-spinner fa-spin");
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            });
        }
    })
    $("#devalider").on('click', async function(){
        const icon = $("#devalider i");
        icon.removeClass('fa-lock-open').addClass("fa-spinner fa-spin");
        try {
            const request = await axios.post('/evaluation/semestre/devalider');
            let response = request.data
            check = 0;
            enableButtons();
            icon.addClass('fa-lock-open').removeClass("fa-spinner fa-spin");
            Toast.fire({
                icon: 'success',
                title: response,
            });
        } catch (error) {
            console.log(error)
            icon.addClass('fa-lock-open').removeClass("fa-spinner fa-spin");
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            });
        }
    })
    $("#enregister").on('click', async function(){
        const icon = $("#enregister i");
        icon.removeClass('fa-check').addClass("fa-spinner fa-spin");
        try {
            const request = await axios.post('/evaluation/semestre/enregistre');
            let response = request.data
            check = 0;
            enableButtons();
            icon.addClass('fa-check').removeClass("fa-spinner fa-spin");
            Toast.fire({
                icon: 'success',
                title: response,
            });
        } catch (error) {
            console.log(error)
            icon.addClass('fa-check').removeClass("fa-spinner fa-spin");
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            });
        }
    })
    $("#imprimer").on("click", () => {  
        $("#imprimer_list").modal("show")
    })
    $("#affichage").on('change', function() {
        let affichage = $(this).val();
        $("#impression_list").attr("href",  $("#impression_list").attr("href").slice(0,-1)+affichage) 
        $("#impression_clair").attr("href",  $("#impression_clair").attr("href").slice(0,-1)+affichage) 
        $("#impression_anonymat").attr("href",  $("#impression_anonymat").attr("href").slice(0,-1)+affichage) 
        $("#impression_rat").attr("href",  $("#impression_rat").attr("href").slice(0,-1)+affichage) 
             
    })
    $("#recalculer").on('click', async function(){
        const icon = $("#recalculer i");
        icon.removeClass('fa-redo-alt').addClass("fa-spinner fa-spin");
        try {
            const request = await axios.post('/evaluation/semestre/recalculer');
            let response = request.data
            icon.addClass('fa-redo-alt').removeClass("fa-spinner fa-spin");
            Toast.fire({
                icon: 'success',
                title: response,
            });
        } catch (error) {
            console.log(error)
            icon.addClass('fa-redo-alt').removeClass("fa-spinner fa-spin");
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            });
        }
    })
    $("#statut").on("click", () => {  
        $("#statut_modal").modal("show")
    })
    $("#statut_avant_rachat").on('click', async function() {
        const icon = $("#statut_avant_rachat i");
        icon.removeClass('fa-sync').addClass("fa-spinner fa-spin");
        try {
            const request = await axios.post('/evaluation/semestre/statut/avantrachat');
            let response = request.data
            icon.addClass('fa-sync').removeClass("fa-spinner fa-spin");
            Toast.fire({
                icon: 'success',
                title: response,
            });
        } catch (error) {
            console.log(error)
            icon.addClass('fa-sync').removeClass("fa-spinner fa-spin");
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            });
        }
    })
    $("#statut_apres_rachat").on('click', async function() {
        const icon = $("#statut_apres_rachat i");
        icon.removeClass('fa-sync').addClass("fa-spinner fa-spin");
        try {
            const request = await axios.post('/evaluation/semestre/statut/apresrachat');
            let response = request.data
            icon.addClass('fa-sync').removeClass("fa-spinner fa-spin");
            Toast.fire({
                icon: 'success',
                title: response,
            });
        } catch (error) {
            console.log(error)
            icon.addClass('fa-sync').removeClass("fa-spinner fa-spin");
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            });
        }
    })
    $("#statut_categorie").on('click', async function() {
        const icon = $("#statut_categorie i");
        icon.removeClass('fa-sync').addClass("fa-spinner fa-spin");
        try {
            const request = await axios.post('/evaluation/semestre/statut/statutsemestrecategorie');
            let response = request.data
            icon.addClass('fa-sync').removeClass("fa-spinner fa-spin");
            Toast.fire({
                icon: 'success',
                title: response,
            });
        } catch (error) {
            console.log(error)
            icon.addClass('fa-sync').removeClass("fa-spinner fa-spin");
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            });
        }
    })
})


    


