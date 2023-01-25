$(document).ready(function () {
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
    let id_bordereau = false;
    let ids_borderaux = [];
    var table_gestion_borderaux = $("#datables_gestion_borderaux").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/honoraire/gestion_borderaux/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        scrollX: true,
        drawCallback: function () {
            ids_borderaux.forEach((e) => {
                $("body tr#" + e)
                .find("input")
                .prop("checked", true);
            });
            $("body tr#" + id_bordereau).addClass('active_databales');
        },
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
    $('body').on('dblclick','#datables_gestion_borderaux tbody tr',function (e) {
        e.preventDefault();
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            id_bordereau = null;
        } else {
            $("#datables_gestion_borderaux tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_bordereau = $(this).attr('id');
        }
    })
    $('body').on('click','#datables_gestion_borderaux tbody tr',function (e) {
        e.preventDefault();
        const input = $(this).find("input");
        if (input.hasClass('check_seance')) {
            return;
        }else{
            if(input.is(":checked")){
                input.prop("checked",false);
                const index = ids_borderaux.indexOf(input.attr("data-id"));
                ids_borderaux.splice(index,1);
            }else{
                input.prop("checked",true);
                ids_borderaux.push(input.attr("data-id"));
            }
        }
    })
    $("select").select2();
    $("#etablissement").on('change', async function (){
        const id_etab = $(this).val();
        table_gestion_borderaux.columns().search("");
        let response = ""
        if(id_etab != "") {
            if($("#semaine").val() != ""){
                table_gestion_borderaux.columns(3).search($("#semaine").val())
            }
            table_gestion_borderaux.columns(0).search(id_etab).draw();
            const request = await axios.get('/api/formation/'+id_etab);
            response = request.data
        }else{
            table_gestion_borderaux.columns().search('').draw();
            if($("#semaine").val() != ""){
                table_gestion_borderaux.columns(3).search($("#semaine").val()).draw();
            }
        }
        $('#promotion').html('').select2();
        $('#formation').html(response).select2();
    })
    $("#formation").on('change', async function (){
        const id_formation = $(this).val();
        table_gestion_borderaux.columns().search("");
        if($("#semaine").val() != ""){
            table_gestion_borderaux.columns(3).search($("#semaine").val())
        }
        let response = ""
        if(id_formation != "") {
            table_gestion_borderaux.columns(1).search(id_formation).draw();
            const request = await axios.get('/api/promotion/'+id_formation);
            response = request.data
        }else{
            table_gestion_borderaux.columns(0).search($("#etablissement").val()).draw();
        }
        $('#promotion').html(response).select2();
    })
    $("#promotion").on('change', async function (){
        const id_promotion = $(this).val();
        table_gestion_borderaux.columns().search("");
        if($("#semaine").val() != ""){
            table_gestion_borderaux.columns(3).search($("#semaine").val())
        }
        if(id_promotion != "") {
            table_gestion_borderaux.columns(2).search(id_promotion).draw();
            const request = await axios.get('/api/semestre/'+id_promotion);
            response = request.data
        }else{
            table_gestion_borderaux.columns(1).search($("#formation").val()).draw();
        }
    })
    $("#semaine").on('change', async function (){
        const semaine = $(this).val();
        table_gestion_borderaux.columns(3).search(semaine).draw();
    })
    $('body').on('click','#imprimer', async function (e) {
        e.preventDefault();
        if(!id_bordereau){
            Toast.fire({
                icon: 'error',
                title: 'Merci de Choisir une ligne!',
            })
            return;
        }
        window.open('/honoraire/creation_borderaux/honoraire_borderaux/'+id_bordereau, '_blank');
    })
    $('body').on('click','#annuler', async function (e) {
        e.preventDefault();
        if(ids_borderaux.length === 0){
            Toast.fire({
                icon: 'error',
                title: 'Merci de Choisir au moins une ligne!',
            })
            return;
        }
        const icon = $("#annuler i");
        icon.removeClass('fa-times-circle').addClass("fa-spinner fa-spin");
        var formData = new FormData();
        formData.append('ids_borderaux', JSON.stringify(ids_borderaux));
        try {
            const request = await axios.post('/honoraire/gestion_borderaux/annuler_borderaux',formData);
            const response = request.data;
            Toast.fire({
                icon: 'success',
                title: response,
            })
            table_gestion_borderaux.ajax.reload(null,false);
            icon.addClass('fa-times-circle').removeClass("fa-spinner fa-spin");
        } catch (error) {
            const message = error.response.data;
            icon.addClass('fa-times-circle').removeClass("fa-spinner fa-spin");
        }
    })
    $('body').on('click','#exporter', async function (e) {
        e.preventDefault();
        if(ids_borderaux.length === 0){
            Toast.fire({
                icon: 'error',
                title: 'Merci de Choisir au moins une ligne!',
            })
            return;
        }
        const icon = $("#exporter i");
        icon.removeClass('fab fa-telegram-plane').addClass("fas fa-spinner fa-spin");
        var formData = new FormData();
        formData.append('ids_borderaux', JSON.stringify(ids_borderaux));
        try {
            const request = await axios.post('/honoraire/gestion_borderaux/exporter_borderaux',formData);
            const response = request.data;
            Toast.fire({
                icon: 'success',
                title: 'Rapprt Bien Générer',
            })
            icon.addClass('fab fa-telegram-plane').removeClass("fas fa-spinner fa-spin");
            window.open('/uploads/honoraire/'+response,'_blank');
        } catch (error) {
            const message = error.response.data;
            icon.addClass('fab fa-telegram-plane').removeClass("fas fa-spinner fa-spin");
        }
    })
    
})