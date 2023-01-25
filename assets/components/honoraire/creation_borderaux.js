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
    var table_creation_borderaux = $("#datables_creation_borderaux").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/honoraire/creation_borderaux/list",
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
    $('body').on('dblclick','#datables_creation_borderaux tbody tr',function (e) {
        e.preventDefault();
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            id_seance = null;
        } else {
            $("#datables_creation_borderaux tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_seance = $(this).attr('id');
        }
    })
    $('body').on('click','#datables_creation_borderaux tbody tr',function (e) {
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
        table_creation_borderaux.columns().search("");
        let response = ""
        if(id_etab != "") {
            if($("#semaine").val() != ""){
                table_creation_borderaux.columns(5).search($("#semaine").val())
            }
            if($("#professeur").val() != ""){
                table_creation_borderaux.columns(6).search($("#professeur").val())
            }
            if($("#grade").val() != ""){
                table_creation_borderaux.columns(4).search($("#grade").val())
            }
            table_creation_borderaux.columns(0).search(id_etab).draw();
            const request = await axios.get('/api/formation/'+id_etab);
            response = request.data
        }else{
            table_creation_borderaux.columns().search('').draw();
            if($("#semaine").val() != ""){
                table_creation_borderaux.columns(5).search($("#semaine").val()).draw();
            }
            if($("#professeur").val() != ""){
                table_creation_borderaux.columns(6).search($("#professeur").val()).draw();
            }
            if($("#grade").val() != ""){
                table_creation_borderaux.columns(4).search($("#grade").val()).draw();
            }
        }
        $('#semestre').html('').select2();
        $('#promotion').html('').select2();
        $('#formation').html(response).select2();
    })
    $("#formation").on('change', async function (){
        const id_formation = $(this).val();
        table_creation_borderaux.columns().search("");
        if($("#semaine").val() != ""){
            table_creation_borderaux.columns(5).search($("#semaine").val())
        }
        if($("#professeur").val() != ""){
            table_creation_borderaux.columns(6).search($("#professeur").val())
        }
        if($("#grade").val() != ""){
            table_creation_borderaux.columns(4).search($("#grade").val())
        }
        let response = ""
        if(id_formation != "") {
            table_creation_borderaux.columns(1).search(id_formation).draw();
            const request = await axios.get('/api/promotion/'+id_formation);
            response = request.data
        }else{
            table_creation_borderaux.columns(0).search($("#etablissement").val()).draw();
        }
        $('#semestre').html('').select2();
        $('#promotion').html(response).select2();
    })
    $("#promotion").on('change', async function (){
        const id_promotion = $(this).val();
        table_creation_borderaux.columns().search("");
        if($("#semaine").val() != ""){
            table_creation_borderaux.columns(5).search($("#semaine").val())
        }
        if($("#professeur").val() != ""){
            table_creation_borderaux.columns(6).search($("#professeur").val())
        }
        if($("#grade").val() != ""){
            table_creation_borderaux.columns(4).search($("#grade").val())
        }
        if(id_promotion != "") {
            table_creation_borderaux.columns(2).search(id_promotion).draw();
            const request = await axios.get('/api/semestre/'+id_promotion);
            response = request.data
        }else{
            table_creation_borderaux.columns(1).search($("#formation").val()).draw();
        }
        $('#semestre').html('').select2();
        $('#semestre').html(response).select2();
    })
    $("#semestre").on('change', async function (){
        const id_semestre = $(this).val();
        table_creation_borderaux.columns().search("");
        if($("#semaine").val() != ""){
            table_creation_borderaux.columns(5).search($("#semaine").val())
        }
        if($("#professeur").val() != ""){
            table_creation_borderaux.columns(6).search($("#professeur").val())
        }
        if($("#grade").val() != ""){
            table_creation_borderaux.columns(4).search($("#grade").val())
        }
        if(id_semestre != "") {
            table_creation_borderaux.columns(3).search(id_semestre).draw();
            const request = await axios.get('/api/module/'+id_semestre);
            response = request.data
        }else{
            table_creation_borderaux.columns(2).search($("#promotion").val()).draw();
        }
    })
    $("#semaine").on('change', async function (){
        const semaine = $(this).val();
        table_creation_borderaux.columns(5).search(semaine).draw();
    })
    $("#semaine").select2({
        minimumInputLength: 10,  // required enter 3 characters or more
        allowClear: true,
        placeholder: '2022-10-10',
        language: "fr",
        ajax: {
           dataType: 'json',
           url: '/honoraire/creation_borderaux/findSemaine',  
           delay: 5,  // ini bebas mau di pake atau tidak
           data: function(params) {
             return {
               search: params.term
             }
           },
           processResults: function (data, page) {
            console.log(data)
           
            var list = {
                text: "Semaine " +data.nsemaine +" de: "+data.debut + " à " +data.fin,
                id: data.id
            }

            return {
                results:  [list]
            };
         },
       }
    })
    $("#professeur").on('change', async function (){
        const id_prof = $(this).val();
        table_creation_borderaux.columns(6).search(id_prof).draw();
    })
    $("#grade").on('change', async function (){
        const grade = $(this).val();
        table_creation_borderaux.columns(4).search(grade).draw();
    })
    $('body').on('click','#cree', async function (e) {
        e.preventDefault();
        if(ids_seances.length === 0 || $("#promotion").val() == "" || $("#semaine").val() == "" ){
            Toast.fire({
                icon: 'error',
                title: 'Merci de Choisir une semestre et une semaine et au moins une ligne!',
            })
            return;
        }
        const icon = $("#cree i");
        icon.removeClass('fa-folder-open').addClass("fa-spinner fa-spin");
        var formData = new FormData();
        formData.append('ids_seances', JSON.stringify(ids_seances)); 
        formData.append('promotion', $("#promotion").val());
        formData.append('semaine', $("#semaine").val());
        try {
            const request = await axios.post('/honoraire/creation_borderaux/cree_borderaux',formData);
            const response = request.data;
            Toast.fire({
                icon: 'success',
                title: 'Borderaux Bien Crée',
            })
            window.open('/honoraire/creation_borderaux/honoraire_borderaux/'+response, '_blank');
            table_creation_borderaux.ajax.reload(null,false);
            icon.addClass('fa-folder-open').removeClass("fa-spinner fa-spin");
        } catch (error) {
            const message = error.response.data;
            icon.addClass('fa-folder-open').removeClass("fa-spinner fa-spin");
        }
    })
    
})