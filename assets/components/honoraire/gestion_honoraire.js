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
    let id_seance = false;
    let ids_seances = [];
    var table_gestion_honoraires = $("#datables_gestion_honoraires").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/honoraire/gestion/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        scrollX: true,
        drawCallback: function () {
            ids_seances.forEach((e) => {
                $("body tr#" + e)
                .find("input")
                .prop("checked", true);
            });
            $("body tr#" + id_seance).addClass('active_databales');
        },
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
    $('body').on('dblclick','#datables_gestion_honoraires tbody tr',function (e) {
        e.preventDefault();
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            id_seance = null;
        } else {
            $("#datables_gestion_honoraires tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_seance = $(this).attr('id');
        }
    })
    $('body').on('click','#datables_gestion_honoraires tbody tr',function (e) {
        e.preventDefault();
        const input = $(this).find("input");
        if (input.hasClass('check_seance')) {
            return;
        }else{
            if(input.is(":checked")){
                input.prop("checked",false);
                const index = ids_seances.indexOf(input.attr("data-id"));
                ids_seances.splice(index,1);
            }else{
                input.prop("checked",true);
                ids_seances.push(input.attr("data-id"));
            }
        }
    })
    $("select").select2();
    $("#etablissement").on('change', async function (){
        const id_etab = $(this).val();
        table_gestion_honoraires.columns().search("");
        let response = ""
        if(id_etab != "") {
            if($("#statut").val() != ""){
                table_gestion_honoraires.columns(4).search($("#statut").val())
            }
            if($("#semaine").val() != ""){
                table_gestion_honoraires.columns(5).search($("#semaine").val())
            }
            if($("#professeur").val() != ""){
                table_gestion_honoraires.columns(6).search($("#professeur").val())
            }
            if($("#grade").val() != ""){
                table_gestion_honoraires.columns(7).search($("#grade").val())
            }
            table_gestion_honoraires.columns(0).search(id_etab).draw();
            const request = await axios.get('/api/formation/'+id_etab);
            response = request.data
        }else{
            // table_creation_borderaux.columns().search('').draw();
            table_gestion_honoraires.columns().search("").draw();
            if($("#statut").val() != ""){
                table_gestion_honoraires.columns(4).search($("#statut").val()).draw();
            }
            if($("#semaine").val() != ""){
                table_gestion_honoraires.columns(5).search($("#semaine").val()).draw();
            }
            if($("#professeur").val() != ""){
                table_gestion_honoraires.columns(6).search($("#professeur").val()).draw();
            }
            if($("#grade").val() != ""){
                table_gestion_honoraires.columns(7).search($("#grade").val()).draw();
            }
        }
        $('#semestre').html('').select2();
        $('#promotion').html('').select2();
        $('#formation').html(response).select2();
    })
    $("#formation").on('change', async function (){
        const id_formation = $(this).val();
        table_gestion_honoraires.columns().search("");
        if($("#semaine").val() != ""){
            table_gestion_honoraires.columns(5).search($("#semaine").val())
        }
        if($("#professeur").val() != ""){
            table_gestion_honoraires.columns(6).search($("#professeur").val())
        }
        if($("#grade").val() != ""){
            table_gestion_honoraires.columns(7).search($("#grade").val())
        }
        let response = ""
        if(id_formation != "") {
            table_gestion_honoraires.columns(1).search(id_formation).draw();
            const request = await axios.get('/api/promotion/'+id_formation);
            response = request.data
        }else{
            table_gestion_honoraires.columns(0).search($("#etablissement").val()).draw();
        }
        $('#semestre').html('').select2();
        $('#promotion').html(response).select2();
    })
    $("#promotion").on('change', async function (){
        const id_promotion = $(this).val();
        table_gestion_honoraires.columns().search("");
        if($("#semaine").val() != ""){
            table_gestion_honoraires.columns(5).search($("#semaine").val())
        }
        if($("#professeur").val() != ""){
            table_gestion_honoraires.columns(6).search($("#professeur").val())
        }
        if($("#grade").val() != ""){
            table_gestion_honoraires.columns(7).search($("#grade").val())
        }
        if(id_promotion != "") {
            table_gestion_honoraires.columns(2).search(id_promotion).draw();
            const request = await axios.get('/api/semestre/'+id_promotion);
            response = request.data
        }else{
            table_gestion_honoraires.columns(1).search($("#formation").val()).draw();
        }
        $('#semestre').html('').select2();
        $('#semestre').html(response).select2();
    })
    $("#semestre").on('change', async function (){
        const id_semestre = $(this).val();
        table_gestion_honoraires.columns().search("");
        if($("#semaine").val() != ""){
            table_gestion_honoraires.columns(5).search($("#semaine").val())
        }
        if($("#professeur").val() != ""){
            table_gestion_honoraires.columns(6).search($("#professeur").val())
        }
        if($("#grade").val() != ""){
            table_gestion_honoraires.columns(7).search($("#grade").val())
        }
        if(id_semestre != "") {
            table_gestion_honoraires.columns(3).search(id_semestre).draw();
            const request = await axios.get('/api/module/'+id_semestre);
            response = request.data
        }else{
            table_gestion_honoraires.columns(2).search($("#promotion").val()).draw();
        }
        $('#element').html('').select2();
        $('#module').html(response).select2();
    })
    $("#module").on('change', async function (){
        const id_module = $(this).val();
        table_gestion_honoraires.columns().search("");
        if($("#semaine").val() != ""){
            table_gestion_honoraires.columns(5).search($("#semaine").val())
        }
        if($("#professeur").val() != ""){
            table_gestion_honoraires.columns(6).search($("#professeur").val())
        }
        if($("#grade").val() != ""){
            table_gestion_honoraires.columns(7).search($("#grade").val())
        }
        if(id_module != "") {
            table_gestion_honoraires.columns(4).search(id_module).draw();
            const request = await axios.get('/api/element/'+id_module);
            response = request.data
        }else{
            table_gestion_honoraires.columns(3).search($("#semestre").val()).draw();
        }
        $('#element').html(response).select2();
    })
    $("#element").on('change', async function (){
        const id_element = $(this).val();
        table_gestion_honoraires.columns().search("");
        if($("#semaine").val() != ""){
            table_gestion_honoraires.columns(5).search($("#semaine").val())
        }
        if($("#professeur").val() != ""){
            table_gestion_honoraires.columns(6).search($("#professeur").val())
        }
        if($("#grade").val() != ""){
            table_gestion_honoraires.columns(7).search($("#grade").val())
        }
        table_gestion_honoraires.columns(5).search(id_element).draw();
    })
    $("#statut").on('change', async function (){
        const statut = $(this).val();
        table_gestion_honoraires.columns(4).search(statut).draw();
    })
    $("#semaine").on('change', async function (){
        const semaine = $(this).val();
        table_gestion_honoraires.columns(5).search(semaine).draw();
    })
    $("#professeur").on('change', async function (){
        const id_prof = $(this).val();
        table_gestion_honoraires.columns(6).search(id_prof).draw();
    })
    $("#grade").on('change', async function (){
        const grade = $(this).val();
        table_gestion_honoraires.columns(7).search(grade).draw();
    })
    $('body').on('click','#annuler', async function (e) {
        e.preventDefault();
        if(ids_seances.length === 0 ){
            Toast.fire({
            icon: 'error',
            title: 'Merci de Choisir au moins une ligne',
            })
            return;
        }
        const icon = $("#annuler i");
        icon.removeClass('fa-times-circle').addClass("fa-spinner fa-spin");
        var formData = new FormData();
        formData.append('ids_seances', JSON.stringify(ids_seances)); 
        try {
            const request = await axios.post('/honoraire/gestion/annuler_honoraires',formData);
            const response = request.data;
            Toast.fire({
                icon: 'success',
                title: 'Honoraire Anullée Avec Succée',
            })
            ids_seances=[];
            table_gestion_honoraires.ajax.reload(null,false);
            icon.addClass('fa-times-circle').removeClass("fa-spinner fa-spin");
        } catch (error) {
            const message = error.response.data;
            icon.addClass('fa-times-circle').removeClass("fa-spinner fa-spin");
        }
    })

    $('body').on('click','#regle', async function (e) {
        e.preventDefault();
        if(ids_seances.length === 0 ){
            Toast.fire({
            icon: 'error',
            title: 'Merci de Choisir au moins une ligne',
            })
            return;
        }
        const icon = $("#regle i");
        icon.removeClass('a-plus-circle').addClass("fa-spinner fa-spin");
        var formData = new FormData();
        formData.append('ids_seances', JSON.stringify(ids_seances)); 
        try {
            const request = await axios.post('/honoraire/gestion/regle_honoraires',formData);
            const response = request.data;
            Toast.fire({
                icon: 'success',
                title: response,
            })
            ids_seances = []
            table_gestion_honoraires.ajax.reload(null,false);
            icon.addClass('a-plus-circle').removeClass("fa-spinner fa-spin");
        } catch (error) {
            const message = error.response.data;
            icon.addClass('a-plus-circle').removeClass("fa-spinner fa-spin");
        }
        
    })
    
})