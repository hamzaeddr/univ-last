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
    $("#enregister, #valider, #devalider, #imprimer").attr('disabled', true)

    const enableButtons = () => {
        $("#imprimer").removeClass('btn-secondary').addClass('btn-info').attr('disabled', false)
        if(check == 0) {
            $("#enregister").removeClass('btn-secondary').addClass('btn-primary').attr('disabled', false)
            $("#valider").removeClass('btn-secondary').addClass('btn-danger').attr('disabled', false)
            $("#devalider").addClass('btn-secondary').removeClass('btn-success').attr('disabled', true)
        } else {
            $("#enregister").addClass('btn-secondary').removeClass('btn-primary').attr('disabled', true)
            $("#valider").addClass('btn-secondary').removeClass('btn-danger').attr('disabled', true)
            $("#devalider").removeClass('btn-secondary').addClass('btn-success').attr('disabled', false)
        }
    }
    const natureDesEpreuves = async (nature) => {
        try {
            const request = await axios.post('/api/nature_erpeuve/'+nature);
            let response = request.data
            $("#nepreuve").html(response).select2();
        } catch (error) {
            
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
        }
    }
    natureDesEpreuves("normale");
    $('.nav-pills a').on('click', function (e) {
        $(this).tab('show')
        $("#list_epreuve_normal").empty()
        if ($(this).html() == 'Session normal') {
            natureDesEpreuves("normale");
            
        } else {
            natureDesEpreuves("rattrapage");
        }   
    
    })
    
    $("#etablissement").select2();
    $("#nepreuve").select2();
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
    $("#semestre").on('change', async function (){
        const id_semestre = $(this).val();
        let response = ""
        if(id_semestre != "") {
            const request = await axios.get('/api/module/'+id_semestre);
            response = request.data
        }
        $('#module').html(response).select2();
    })
    $("#module").on('change', async function (){
        const id_module = $(this).val();
        let response = ""
        if(id_module != "") {
            const request = await axios.get('/api/element/'+id_module);
            response = request.data
        }
        $('#element').html(response).select2();
    })

    $("#get_list_etudiant").on('click', async function(e){
        e.preventDefault();
        $("#list_epreuve_normal").empty()
        let element_id = $('#element').val();
        let nature_epreuve_id = $('#nepreuve').val();
        if(element_id == "" || !element_id) {
            Toast.fire({
                icon: 'error',
                title: 'Veuillez selection element!',
            })
            return;
        }
        if(nature_epreuve_id == "" || !nature_epreuve_id) {
            Toast.fire({
                icon: 'error',
                title: 'Veuillez selection nature d\'epreuve!',
            })
            return;
        }
        const icon = $("#get_list_etudiant i");
        icon.removeClass('fa-search').addClass("fa-spinner fa-spin");
        try {
            let formData = new FormData();
            formData.append("order", $("#order").val())
            const request = await axios.post('/evaluation/epreuve/list/'+element_id+'/'+nature_epreuve_id, formData);
            let response = request.data
            if ($.fn.DataTable.isDataTable("#list_epreuve_normal")) {
                $('#list_epreuve_normal').DataTable().clear().destroy();
            }
            $("#list_epreuve_normal").html(response.html).DataTable({
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
            const request = await axios.post('/evaluation/epreuve/valider');
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
            const request = await axios.post('/evaluation/epreuve/devalider');
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
            const request = await axios.post('/evaluation/epreuve/enregistre');
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

    
})


    


