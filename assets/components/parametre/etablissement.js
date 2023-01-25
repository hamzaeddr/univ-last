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
    let id_etab;
   
    var table = $("#datatables_gestion_etablissement").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/parametre/etablissement/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
    $('body').on('click','#datatables_gestion_etablissement tbody tr',function () {
        // const input = $(this).find("input");
        
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            id_etab = null;
        } else {
            $("#datatables_gestion_etablissement tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_etab = $(this).attr('id');
           
        }
        
    })

    $("#ajouter").on("click", () => {
        $("#ajout_modal").modal("show")
    })
    $("#modifier").on("click", async function(){
        if(!id_etab){
            Toast.fire({
              icon: 'error',
              title: 'Veuillez selectioner une ligne!',
            })
            return;
        }
        const icon = $("#modifier i");

        try {
            icon.remove('fa-edit').addClass("fa-spinner fa-spin ");
            const request = await axios.get('/parametre/etablissement/details/'+id_etab);
            const response = request.data;
            console.log(response)
            icon.addClass('fa-edit').removeClass("fa-spinner fa-spin ");
            $("#modifier_modal #designation").val(response.designation)
            $("#modifier_modal #abreviation").val(response.abreviation)
            $("#modifier_modal #nature").val(response.nature)
            $("#modifier_modal #date").val(response.date)
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
    $("#etablissemnt_save").on("submit", async (e) => {
        e.preventDefault();
        var formData = new FormData($("#etablissemnt_save")[0])
        // var formData = [...new FormData($("#etablissemnt_save")[0])]
        // var data = Object.fromEntries(formData);
       
        const icon = $("#etablissemnt_save i");

        try {
            icon.remove('fa-check-circle').addClass("fa-spinner fa-spin ");
            const request = await axios.post('/parametre/etablissement/new', formData);
            const response = request.data;
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
            $('#etablissemnt_save')[0].reset();
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
    $("#etablissemnt_udpate").on("submit", async (e) => {
        e.preventDefault();
        var formData = new FormData($("#etablissemnt_udpate")[0])
       
        const icon = $("#etablissemnt_udpate i");

        try {
            icon.remove('fa-check-circle').addClass("fa-spinner fa-spin ");
            const request = await axios.post('/parametre/etablissement/update/'+id_etab, formData);
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


