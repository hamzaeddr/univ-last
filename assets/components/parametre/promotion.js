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
    let id_promotion;
   
    var table = $("#datatables_gestion_promotion").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/parametre/promotion/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
    $("#etablissement").select2();
    $('body').on('click','#datatables_gestion_promotion tbody tr',function () {
        // const input = $(this).find("input");
        
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            id_promotion = null;
        } else {
            $("#datatables_gestion_promotion tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_promotion = $(this).attr('id');   
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
        if(!id_promotion){
            Toast.fire({
              icon: 'error',
              title: 'Veuillez selectioner une ligne!',
            })
            return;
        }
        const icon = $("#modifier i");

        try {
            icon.remove('fa-edit').addClass("fa-spinner fa-spin ");
            const request = await axios.get('/parametre/promotion/details/'+id_promotion);
            const response = request.data;
            console.log(response)
            icon.addClass('fa-edit').removeClass("fa-spinner fa-spin ");
            $("#modifier_modal #designation").val(response.designation)
            $("#modifier_modal #ordre").val(response.ordre)
            if(response.active == 1){
                $("#modifier_modal #active").prop("checked", true)
            }else {
                $("#modifier_modal #active").prop("checked", false)
            }
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
        var formData = new FormData($("#save")[0])
        formData.append("formation_id", $("#formation").val());
        const icon = $("#save i");

        try {
            icon.remove('fa-check-circle').addClass("fa-spinner fa-spin ");
            const request = await axios.post('/parametre/promotion/new', formData);
            const response = request.data;
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
            $('#save')[0].reset();
            table.ajax.reload();
            $("#ajout_modal").modal("hide")
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
    $("#udpate").on("submit", async (e) => {
        e.preventDefault();
        var formData = new FormData($("#udpate")[0])
       
        const icon = $("#udpate i");

        try {
            icon.remove('fa-check-circle').addClass("fa-spinner fa-spin ");
            const request = await axios.post('/parametre/promotion/update/'+id_promotion, formData);
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
   
})


