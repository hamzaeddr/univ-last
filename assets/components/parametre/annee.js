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
    let id_annee;
   
    var table = $("#datatables_gestion_annee").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/parametre/annee/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
    $("#etablissement").select2();
    $('body').on('click','#datatables_gestion_annee tbody tr',function () {
        // const input = $(this).find("input");
        
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            id_annee = null;
        } else {
            $("#datatables_gestion_annee tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_annee = $(this).attr('id');   
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
    $("#ajouter").on("click", () => {
        // alert($("#formation").val())
        if(!$("#formation").val() || $("#formation").val() == ""){
            Toast.fire({
              icon: 'error',
              title: 'Veuillez choissir une formation!',
            })
            return;
        }
        $("#ajout_modal").modal("show")

    })
    $("#modifier").on("click", async function(){
        if(!id_annee){
            Toast.fire({
              icon: 'error',
              title: 'Veuillez selectioner une ligne!',
            })
            return;
        }
        const icon = $("#modifier i");
        try {
            icon.remove('fa-edit').addClass("fa-spinner fa-spin ");
            const request = await axios.get('/parametre/annee/details/'+id_annee);
            const response = request.data;
            console.log(response)
            icon.addClass('fa-edit').removeClass("fa-spinner fa-spin ");
            $("#modifier_modal #designation").val(response.designation)
            $("#modifier_modal").modal("show")
        } catch (error) {
            console.log(error, error.response);
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
              })
            icon.addClass('fa-edit').removeClass("fa-spinner fa-spin ");
        }

    })
    $("#save").on("submit", async (e) => {
        e.preventDefault();
        if($("#formation").val() == ""){
            Toast.fire({
              icon: 'error',
              title: 'Veuillez selectioner une formation!',
            })
            return;
        }
        var formData = new FormData($("#save")[0])
        formData.append("formation_id", $("#formation").val());
        const icon = $("#save i");
        try {
            icon.remove('fa-check-circle').addClass("fa-spinner fa-spin ");
            const request = await axios.post('/parametre/annee/new', formData);
            const response = request.data;
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
            $('#save')[0].reset();
            table.ajax.reload();
            $("#ajout_modal").modal("hide")
        } catch (error) {
            // console.log(error, error.response);
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
              })
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
        }
    })
    $("#udpate").on("submit", async (e) => {
        e.preventDefault();
        if(!id_annee){
            Toast.fire({
              icon: 'error',
              title: 'Veuillez selectioner une ligne!',
            })
            return;
        }
        var formData = new FormData($("#udpate")[0])
        const icon = $("#udpate i");

        try {
            icon.remove('fa-check-circle').addClass("fa-spinner fa-spin ");
            const request = await axios.post('/parametre/annee/update/'+id_annee, formData);
            const response = request.data;
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
            table.ajax.reload();
            $("#modifier_modal").modal("hide")
        } catch (error) {
            console.log(error, error.response);
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
              })
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
            
        }
    })
    
    $('body').on('click','#supprimer',async function (e) {
        e.preventDefault();
        if(!id_annee){
            Toast.fire({
              icon: 'error',
              title: 'Veuillez selectioner une ligne!',
            })
            return;
        }
        const icon = $("#udpate i");
        try {
            icon.remove('fa-trash').addClass("fa-spinner fa-spin ");
            const request = await axios.post('/parametre/annee/delete/'+id_annee);
            const response = request.data;
            icon.addClass('fa-trash').removeClass("fa-spinner fa-spin ");
            table.ajax.reload();
            Toast.fire({
                icon: 'success',
                title: response,
            })
            $("#modifier_modal").modal("hide")
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

    
    $('body').on('click','.btn_active',async function (e) {
        e.preventDefault();
        const icon = $(this).find('i');
        icon.removeClass('fa-power-off').addClass("fa-spinner fa-spin");
        let annee = $(this).attr('id');
        try{
            const request = await  axios.post('/parametre/annee/active_annee/'+annee)
            table.ajax.reload(null, false);
            icon.removeClass('fa-spinner fa-spin').addClass("fa-power-off");
        }catch(error){
            const message = error.response.data;
            icon.removeClass('fa-spinner fa-spin').addClass("fa-power-off");
        }
    });
   
})


