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
    var table_generation_honoraires = $("#datables_generation_honoraires").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/honoraire/generation/list",
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
    $('body').on('dblclick','#datables_generation_honoraires tbody tr',function (e) {
        e.preventDefault();
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            id_seance = null;
        } else {
            $("#datables_generation_honoraires tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_seance = $(this).attr('id');
        }
    })
    $('body').on('click','#datables_generation_honoraires tbody tr',function (e) {
        e.preventDefault();
        const input = $(this).find("input");
        if (input.hasClass('check_reg')) {
            return;
        }
        else{
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
        table_generation_honoraires.columns().search("");
        let response = ""
        if(id_etab != "") {
            if($("#semaine").val() != ""){
                table_generation_honoraires.columns(6).search($("#semaine").val())
            }
            if($("#professeur").val() != ""){
                table_generation_honoraires.columns(7).search($("#professeur").val())
            }
            if($("#grade").val() != ""){
                table_generation_honoraires.columns(8).search($("#grade").val())
            }
            if($("#annuler").val() != ""){
                table_generation_honoraires.columns(9).search($("#niv").val())
            }
            table_generation_honoraires.columns(0).search(id_etab).draw();
            const request = await axios.get('/api/formation/'+id_etab);
            response = request.data
        }else{
            table_generation_honoraires.columns().search("").draw();
            if($("#semaine").val() != ""){
                table_generation_honoraires.columns(6).search($("#semaine").val()).draw();
            }
            if($("#professeur").val() != ""){
                table_generation_honoraires.columns(7).search($("#professeur").val()).draw();
            }
            if($("#grade").val() != ""){
                table_generation_honoraires.columns(8).search($("#grade").val()).draw();
            }
        }
        $('#niv1').html('').select2();
        $('#niv2').html('').select2();
        $('#niv3').html('').select2();
        $('#semestre').html('').select2();
        $('#module').html('').select2();
        $('#element').html('').select2();
        $('#promotion').html('').select2();
        $('#formation').html(response).select2();
    })
    $("#formation").on('change', async function (){
        const id_formation = $(this).val();
        table_generation_honoraires.columns().search("");
        if($("#semaine").val() != ""){
            table_generation_honoraires.columns(6).search($("#semaine").val())
        }
        if($("#professeur").val() != ""){
            table_generation_honoraires.columns(7).search($("#professeur").val())
        }
        if($("#grade").val() != ""){
            table_generation_honoraires.columns(8).search($("#grade").val())
        }
        let response = ""
        if(id_formation != "") {
            table_generation_honoraires.columns(1).search(id_formation).draw();
            const request = await axios.get('/api/promotion/'+id_formation);
            response = request.data
        }else{
            table_generation_honoraires.columns(0).search($("#etablissement").val()).draw();
        }
        $('#niv1').html('').select2();
        $('#niv2').html('').select2();
        $('#niv3').html('').select2();
        $('#semestre').html('').select2();
        $('#module').html('').select2();
        $('#element').html('').select2();
        $('#promotion').html(response).select2();
    })
    $("#promotion").on('change', async function (){
        const id_promotion = $(this).val();
        table_generation_honoraires.columns().search("");
        if($("#semaine").val() != ""){
            table_generation_honoraires.columns(6).search($("#semaine").val())
        }
        if($("#professeur").val() != ""){
            table_generation_honoraires.columns(7).search($("#professeur").val())
        }
        if($("#grade").val() != ""){
            table_generation_honoraires.columns(8).search($("#grade").val())
        }
        if(id_promotion != "") {
            table_generation_honoraires.columns(2).search(id_promotion).draw();
            const request = await axios.get('/api/semestre/'+id_promotion);
            response = request.data
            const requestt = await axios.get('/api/niv1/'+id_promotion);
            niv1 = requestt.data 
        }else{
            table_generation_honoraires.columns(1).search($("#formation").val()).draw();
        }
        $('#niv1').html(niv1).select2();
        $('#niv2').html('').select2();
        $('#niv3').html('').select2();
        $('#semestre').html('').select2();
        $('#module').html('').select2();
        $('#element').html('').select2();
        $('#semestre').html(response).select2();
    })
    $("#semestre").on('change', async function (){
        const id_semestre = $(this).val();
        table_generation_honoraires.columns().search("");
        if($("#semaine").val() != ""){
            table_generation_honoraires.columns(6).search($("#semaine").val())
        }
        if($("#professeur").val() != ""){
            table_generation_honoraires.columns(7).search($("#professeur").val())
        }
        if($("#grade").val() != ""){
            table_generation_honoraires.columns(8).search($("#grade").val())
        }
        if($("#niv1").val() != ""){
            table_generation_honoraires.columns(9).search($("#niv1").val())
        }
        if($("#niv2").val() != ""){
            table_generation_honoraires.columns(10).search($("#niv2").val())
        }
        if($("#niv3").val() != ""){
            table_generation_honoraires.columns(11).search($("#niv3").val())
        }
        if(id_semestre != "") {
            table_generation_honoraires.columns(3).search(id_semestre).draw();
            const request = await axios.get('/api/module/'+id_semestre);
            response = request.data
        }else{
            table_generation_honoraires.columns(2).search($("#promotion").val()).draw();
        }
        $('#element').html('').select2();
        $('#module').html(response).select2();
    })
    $("#module").on('change', async function (){
        const id_module = $(this).val();
        table_generation_honoraires.columns().search("");
        if($("#semaine").val() != ""){
            table_generation_honoraires.columns(6).search($("#semaine").val())
        }
        if($("#professeur").val() != ""){
            table_generation_honoraires.columns(7).search($("#professeur").val())
        }
        if($("#grade").val() != ""){
            table_generation_honoraires.columns(8).search($("#grade").val())
        }
        if($("#niv1").val() != ""){
            table_generation_honoraires.columns(9).search($("#niv1").val())
        }
        if($("#niv2").val() != ""){
            table_generation_honoraires.columns(10).search($("#niv2").val())
        }
        if($("#niv3").val() != ""){
            table_generation_honoraires.columns(11).search($("#niv3").val())
        }
        if(id_module != "") {
            table_generation_honoraires.columns(4).search(id_module).draw();
            const request = await axios.get('/api/element/'+id_module);
            response = request.data
        }else{
            table_generation_honoraires.columns(3).search($("#semestre").val()).draw();
        }
        $('#element').html(response).select2();
    })
    $("#element").on('change', async function (){
        const id_element = $(this).val();
        table_generation_honoraires.columns().search("");
        if($("#semaine").val() != ""){
            table_generation_honoraires.columns(6).search($("#semaine").val())
        }
        if($("#professeur").val() != ""){
            table_generation_honoraires.columns(7).search($("#professeur").val())
        }
        if($("#grade").val() != ""){
            table_generation_honoraires.columns(8).search($("#grade").val())
        }
        if($("#niv1").val() != ""){
            table_generation_honoraires.columns(9).search($("#niv1").val())
        }
        if($("#niv2").val() != ""){
            table_generation_honoraires.columns(10).search($("#niv2").val())
        }
        if($("#niv3").val() != ""){
            table_generation_honoraires.columns(11).search($("#niv3").val())
        }
        table_generation_honoraires.columns(5).search(id_element).draw();
    })
    $("#semaine").on('change', async function (){
        const semaine = $(this).val();
        table_generation_honoraires.columns(6).search(semaine).draw();
    })
    $("#professeur").on('change', async function (){
        const id_prof = $(this).val();
        table_generation_honoraires.columns(7).search(id_prof).draw();
    })
    $("#grade").on('change', async function (){
        const grade = $(this).val();
        table_generation_honoraires.columns(8).search(grade).draw();
    })
    $("#niv1").on('change', async function (){
        const niv1 = $(this).val();
        let response = ""
        if(niv1 != "") {
            niv = niv1;
            const request = await axios.get('/api/niv2/'+niv1);
            response = request.data
        }else{
            niv = 0;
        }
        table_generation_honoraires.columns(9).search(niv1).draw();
        $('#niv3').html("").select2();
        $('#niv2').html(response).select2();
    })
    $("#niv2").on('change', async function (){
        const niv2 = $(this).val();
        let response = ""
        if(niv2 != "") {
            niv = niv2;
            const request = await axios.get('/api/niv3/'+niv2);
            response = request.data
        }else{
            niv = $('#niv1').val();
        }
        $('#niv3').html(response).select2();
        table_generation_honoraires.columns(10).search(niv2).draw();
    })
    $("#niv3").on('change', async function (){
        const niv3 = $(this).val();
        let response = ""
        if(niv3 != "") {
            niv = niv3;
        }else{
            niv = $('#niv2').val();
        }
        table_generation_honoraires.columns(11).search(niv3).draw();
    })
    $('body').on('click','#generer', async function (e) {
        e.preventDefault();
        if(ids_seances.length === 0 ){
            Toast.fire({
            icon: 'error',
            title: 'Merci de Choisir au moins une ligne',
            })
            return;
        }
        const icon = $("#generer i");
        icon.removeClass('fab fa-get-pocket').addClass("fas fa-spinner fa-spin");
        var formData = new FormData();
        formData.append('ids_seances', JSON.stringify(ids_seances)); 
        try {
            const request = await axios.post('/honoraire/generation/generation_honoraire_generer',formData);
            const response = request.data;
            Toast.fire({
                icon: 'success',
                title: response,
            })
            table_generation_honoraires.ajax.reload(null,false);
            ids_seances = [];
            icon.addClass('fab fa-get-pocket').removeClass("fas fa-spinner fa-spin");
        } catch (error) {
            const message = error.response.data;
            icon.addClass('fab fa-get-pocket').removeClass("fas fa-spinner fa-spin");
        }
    })
    
})