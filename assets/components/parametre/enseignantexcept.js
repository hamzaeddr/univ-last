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
    
    
    $(document).ready(function  () {
    let id_enseignantexcept;
   
    var table = $("#datatables_gestion_enseignantexcept").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/parametre/enseignantexcept/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
    $("select").select2();
    $('body').on('click','#datatables_gestion_enseignantexcept tbody tr',function () {
        // const input = $(this).find("input");
        
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            id_enseignantexcept = null;
        } else {
            $("#datatables_gestion_enseignantexcept tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_enseignantexcept = $(this).attr('id');   
        }
        
    })
    $("#etablissement").on("change",async function(){
        const id_etab = $(this).val();
        let response = ""
        if(id_etab != "") {
            const request = await axios.get('/api/formation/'+id_etab);
            response = request.data
            table.columns(0).search($(this).val()).draw();
        } else {
            table.columns(0).search("").draw();
        }
        $('#formation').html(response).select2();
    })
    $("#formation").on('change', async function (){
        const id_formation = $(this).val();
        if(id_formation != "") {
            table.columns(1).search(id_formation).draw();
        } else {
            table.columns(1).search("").draw();
        }
       
    })
    $("#enseignant").on('change', async function (){
        const id_enseignant = $(this).val();
        if(id_enseignant != "") {
            table.columns(2).search(id_enseignant).draw();
        } else {
            table.columns(2).search("").draw();
        }
    })
    $("#ajouter").on("click", async function(e){
        e.preventDefault();
        if($("#formation").val() == "" || $("#enseignant").val() == ""){
            Toast.fire({
              icon: 'error',
              title: 'Veuillez choissir une formation et un enseignant!',
            })
            return;
        }
        const icon = $("#ajouter i");
        icon.removeClass('fa-plus').addClass("fa-spinner fa-spin ");
        var formData = new FormData()
        formData.append("formation_id", $("#formation").val());
        formData.append("enseignant_id", $("#enseignant").val());
        try {
            const request = await axios.post('/parametre/enseignantexcept/new',formData);
            const response = request.data;
            table.ajax.reload()
            icon.addClass('fa-plus').removeClass("fa-spinner fa-spin ");
            Toast.fire({
                icon: 'success',
                title: response,
            })
        } catch (error) {
            console.log(error, error.response);
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            })
            icon.addClass('fa-plus').removeClass("fa-spinner fa-spin ");
            
        }
    })
    $("#supprimer").on("click", async function(){
        if(!id_enseignantexcept){
            Toast.fire({
              icon: 'error',
              title: 'Veuillez selectioner un enseignant!',
            })
            return;
        }
        const icon = $("#supprimer i");
        icon.removeClass('fa-trash').addClass("fa-spinner fa-spin ");
        var formData = new FormData()
        formData.append("enseignantexcept", id_enseignantexcept);
        try {
            const request = await axios.post('/parametre/enseignantexcept/delete',formData);
            const response = request.data;
            id_enseignantexcept = null;
            table.ajax.reload()
            icon.addClass('fa-trash').removeClass("fa-spinner fa-spin ");
            Toast.fire({
                icon: 'success',
                title: response,
            })
        } catch (error) {
            console.log(error, error.response);
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            })
            icon.addClass('fa-trash').removeClass("fa-spinner fa-spin ");
        }
    })
})


