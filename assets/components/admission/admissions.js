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
let id_etudiant = false;
let idpreins = [];
let idAdmissions = [];

$(document).ready(function  () {
var table = $("#datatables_candidat_admissibles").DataTable({
    lengthMenu: [
        [10, 15, 25, 50, 100, 20000000000000],
        [10, 15, 25, 50, 100, "All"],
    ],
    order: [[0, "desc"]],
    ajax: "/admission/admissions/candidat_addmissible_list",
    processing: true,
    serverSide: true,
    deferRender: true,
    drawCallback: function () {
        idpreins.forEach((e) => {
            $("body tr#" + e)
            .find("input")
            .prop("checked", true);
        });
    },
    language: {
        url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
    },
    });
var tableAdmis = $("#datatables_candidat_admis").DataTable({
    lengthMenu: [
        [10, 15, 25, 50, 100, 20000000000000],
        [10, 15, 25, 50, 100, "All"],
    ],
    order: [[0, "desc"]],
    ajax: "/admission/admissions/candidat_admis_list",
    processing: true,
    serverSide: true,
    deferRender: true,
    // drawCallback: function () {
    //     idpreins.forEach((e) => {
    //         $("body tr#" + e)
    //         .find("input")
    //         .prop("checked", true);
    //     });
    // },
    language: {
        url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
    },
    });
$('.nav-pills a').on('click', function (e) {
    $(this).tab('show')
    if ($(this).html() == 'Candidats Admissibles') {
      $('.admissible_action').show('fast')
      $('.admis_action').hide('fast')
    } else {
        $('.admissible_action').hide('fast')
        $('.admis_action').show('fast')
    }
    })
$('body').on('click','#datatables_candidat_admissibles tbody tr',function () {
    const input = $(this).find("input");
    if(input.is(":checked")){
        input.prop("checked",false);
        const index = idpreins.indexOf(input.attr("id"));
        idpreins.splice(index,1);
    }else{
        input.prop("checked",true);
        idpreins.push(input.attr("id"));
    }
    console.log(idpreins);
})
$('body').on('click','#datatables_candidat_admis tbody tr',function () {
    const input = $(this).find("input");
    if(input.is(":checked")){
        input.prop("checked",false);
        const index = idAdmissions.indexOf(input.attr("id"));
        idAdmissions.splice(index,1);
    }else{
        input.prop("checked",true);
        idAdmissions.push(input.attr("id"));
    }
})
$('#admission').on('click', async (e) => {
    e.preventDefault();
    if(idpreins.length < 1){
        Toast.fire({
          icon: 'error',
          title: 'Veuillez cocher une or plusieurs ligne!',
        })
        return;
    }
    const icon = $("#admission i");
    icon.removeClass('fa-check').addClass("fa-spinner fa-spin");
    
    var formData = new FormData();
    formData.append('idpreins', JSON.stringify(idpreins));
    try {
        const request = await axios.post("/admission/admissions/new", formData);
        const data = await request.data;
        Toast.fire({
            icon: 'success',
            title: 'Admissions Bien Enregister',
        })
        icon.addClass('fa-check').removeClass("fa-spinner fa-spin");

        table.ajax.reload(null, false);
        tableAdmis.ajax.reload(null, false);
      } catch (error) {
        const message = error.response.data;
        console.log(error, error.response);
        Toast.fire({
            icon: 'error',
            title: 'Some Error',
        })
        icon.addClass('fa-check').removeClass("fa-spinner fa-spin");

      }
})
$('#annuler').on('click', async (e) => {
    e.preventDefault();
    if(idAdmissions.length < 1){
        Toast.fire({
          icon: 'error',
          title: 'Veuillez cocher une or plusieurs ligne!',
        })
        return;
    }
    const icon = $("#annuler i");
    icon.removeClass('fa-exclamation-triangle').addClass("fa-spinner fa-spin");
    
    var formData = new FormData();
    formData.append('idAdmissions', JSON.stringify(idAdmissions));
    try {
        const request = await axios.post("/admission/admissions/annuler", formData);
        const data = await request.data;
        if(data.error) {
            Toast.fire({
                icon: 'error',
                title: data.error,
            })
        } else {
            Toast.fire({
                icon: 'success',
                title: 'Admissions Bien Annuler',
            })
        }
        icon.addClass('fa-exclamation-triangle').removeClass("fa-spinner fa-spin");

        tableAdmis.ajax.reload(null, false);
        table.ajax.reload(null, false);
      } catch (error) {
        const message = error.response.data;
        console.log(error, error.response);
        Toast.fire({
            icon: 'error',
            title: 'Some Error',
        })
        icon.addClass('fa-exclamation-triangle').removeClass("fa-spinner fa-spin");

    }
})



})
