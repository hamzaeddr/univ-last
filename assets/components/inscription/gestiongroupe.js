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
    let id_inscription = false;
    let idInscription = [];
    let frais = [];
    
    $(document).ready(function  () {
    var table = $("#datatables_gestion_inscription").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/inscription/groupes/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        responsive: true,
        scrollX: true,
        drawCallback: function () {
            idInscription.forEach((e) => {
                $("body tr#" + e)
                .find("input")
                .prop("checked", true);
            });
            $("body tr#" + id_inscription).addClass('active_databales')
        },
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
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
            $('#promotion').html("").select2();
        }
        $('#formation').html(response).select2();
    })
    $("#formation").on('change', async function (){
        const id_formation = $(this).val();
        table.columns().search("");
        let responseAnnee = ""
        let responsePromotion = ""
        if(id_formation != "") {
            table.columns(1).search(id_formation).draw();
            const requestPromotion = await axios.get('/api/promotion/'+id_formation);
            responsePromotion = requestPromotion.data
            const requestAnnee = await axios.get('/api/annee/'+id_formation);
            responseAnnee = requestAnnee.data
        } else {
            table.columns(0).search($("#etablissement").val()).draw();
        }
        $('#annee').html(responseAnnee).select2();
        $('#promotion').html(responsePromotion).select2();
    })
    
    $("#promotion").on('change', async function (){
        table.columns().search("");
        if($(this).val() != "") {
            if($("#annee").val() != "") {
                table.columns(3).search($("#annee").val());
            }
            table.columns(2).search($(this).val()).draw();
        } else {
            table.columns(1).search($("#formation").val()).draw();
        }

    })
    $("#annee").on('change', async function (){
        table.columns().search("");
        if($(this).val() != "") {
            table.columns(3).search($(this).val());
        } 
        table.columns(2).search($("#promotion").val()).draw();
    })

    $('body').on('click','#datatables_gestion_inscription tbody tr',function () {
        const input = $(this).find("input");
        if(input.is(":checked")){
            input.prop("checked",false);
            const index = idInscription.indexOf(input.attr("id"));
            idInscription.splice(index,1);
        }else{
            input.prop("checked",true);
            idInscription.push(input.attr("id"));
        }
    })
    $('body').on('dblclick','#datatables_gestion_inscription tbody tr',function () {
        // const input = $(this).find("input");
        
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            id_inscription = null;
        } else {
            $("#datatables_gestion_inscription tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_inscription = $(this).attr('id');
            getStatutInscription();
            getInscriptionInfos();
            getFrais();
        }
        
    })

    $("body").on('click','#import', async function (e){
        e.preventDefault();
        $('#import_affectation').modal("show");
    })
    
    $('body').on('click','#affectation_canvas', function (){
        window.open('/inscription/groupes/affectation_canvas', '_blank');
    })

    $('body').on('click','#groupes_canvas', function (){
        window.open('/inscription/groupes/groupes_canvas', '_blank');
    })

    $("#import_groupes_save").on("submit", async function(e) {
        e.preventDefault();
        let formData = new FormData($(this)[0]);
        let modalAlert = $("#import_affectation .modal-body .alert")
        modalAlert.remove();
        const icon = $("#affectation_enregistre i");
        icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
        try {
            const request = await axios.post('/inscription/groupes/import_groupe', formData);
            const response = request.data;
            $("#import_affectation .modal-body").prepend(
                `<div class="alert alert-success">
                    <p>${response}</p>
                </div>`
            );
            table.ajax.reload(null,false);
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            modalAlert.remove();
            $("#import_affectation .modal-body").prepend(
                `<div class="alert alert-danger">${message}</div>`
            );
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
        }
        setTimeout(() => {
            $("#import_affectation .modal-body .alert").remove();
        }, 4000);
    })
    
    $("#export").on("click", function(e) {
        e.preventDefault();
        // if($("#promotion").val() == "" || $("#annee").val() == ""){
        //     Toast.fire({
        //         icon: 'error',
        //         title: 'Merci de Choisir une Promotion, Une Année!',
        //     })
        //     return;
        // }
        // if($("#formation").val() == "" || $("#annee").val() == ""){
        //     Toast.fire({
        //         icon: 'error',
        //         title: 'Merci de Choisir une formation, Une Année!',
        //     })
        //     return;
        // }
        window.open('/inscription/groupes/exportAllgroupes', '_blank');
        // window.open('/inscription/groupes/exportbyformation/'+$("#annee").val(), '_blank');
        // window.open('/inscription/groupes/exportbypromotion/'+$("#promotion").val()+'/'+$("#annee").val(), '_blank');
    })
    // })

})

