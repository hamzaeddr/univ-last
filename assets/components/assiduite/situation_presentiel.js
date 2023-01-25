const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.addEventListener("mouseenter", Swal.stopTimer);
      toast.addEventListener("mouseleave", Swal.resumeTimer);
    },
  });
  $(document).ready(function () {

    /////////////////dropdown-etdiants////////////////////////////

    function etudiant_situation_affichage(var1) {

        $(".loader").show();
          $.ajax({
            type: "POST",
            url: "/api/etud_aff_situation/" + var1,
            success: function (html) {
        $(".loader").hide();
        $("#Et_situation").html(html);   
          
            },
          });
          return var1;
        }
        $("#E_situation").prop("selectedIndex", 1);
        $("#F_situation").prop("selectedIndex", 1);
        $("#P_situation").prop("selectedIndex", 1);
   ///////////////etablissement//////////

   $("#E_situation").on("change", function () {
    var etablissement = $(this).val();
    $.ajax({
      type: "POST",
      url: "/api/Formation_aff/" + etablissement,
      success: function (html) {
        $("#F_situation").html(html);
        $("#F_situation").prop("selectedIndex", 1);

        $.ajax({
          type: "POST",
          url: "/api/Promotion_aff/" + $("#F_situation").val(),
          success: function (html) {
            $("#P_situation").html(html);
            $("#P_situation").prop("selectedIndex", 1);
            etudiant_situation_affichage($("#P_situation").val());
            
            
          },
        });
      },
    });
  });
  ///////////////Fomation//////////

  $("#F_situation").on("change", function () {
    var formation = $(this).val();
    $.ajax({
      type: "POST",
      url: "/api/Promotion_aff/" + formation,
      success: function (html) {
        $("#P_situation").html(html);
        $("#P_situation").prop("selectedIndex", 1);
        etudiant_situation_affichage($("#P_situation").val());

      },
    });
  });
  ///////////////Promotion//////////

  $("#P_situation").on("change", function () {
    var promotion = $(this).val();
    etudiant_situation_affichage(promotion);

  });

  ////////////////////////////////:: situation presentil pdf  ////////////////////////////////////:
  $("body #situation_presentiel").on("click", function () {
    // list.forEach((obj) => {
      var etudiant = $("#Et_situation").val();
      // var date_debut = $("#datetimeDsituation").val();
      // var date_fin = $("#datetimeFsituation").val();

    window.open('/assiduite/assiduites/pdf_presentiel/'+etudiant, '_blank');


});    
    
            
 //////////////////extraction////////////////:
 $('#create_extraction').click(function(){ 

    var to = $('#datetimeFsituation').val();
    var from = $('#datetimeDsituation').val();
    var service = $('#E_situation').val();
    var formation = $('#F_situation').val();
    var promotion = $('#P_situation').val();
  
  
    var tou =  $('input[name="tous"]:checked').val();
    
            // window.location.href = "{{ path('extraction') }}?To="+to+"&From="+from;
           url = "/api/generate_extraction?To="+to+"&From="+from+"&formation="+formation+"&promotion="+promotion+"&Service="+service+"&Tou="+tou+"&type=normal";;
           window.open(url);
             
  
// });
});




$("body #situation_search").on("click", function () {
    etudiant = $("#Et_situation").val();
    dated = $("#datetimeDsituation").val();
    datef = $("#datetimeFsituation").val();
    $.ajax({
      type: "POST",
      url: "/api/aff_situation",
      data: {
        etudiant : etudiant,
        dated : dated,
        datef : datef ,
      },
      success: function (html) {
        if ($.fn.DataTable.isDataTable("#dtDynamicVerticalScrollExample_situation")) {
          $("#dtDynamicVerticalScrollExample_situation").DataTable().clear().destroy();
        }
        $("#dtDynamicVerticalScrollExample_situation")
          .html(html)
          .DataTable({
            bLengthChange: false,
            lengthMenu: [
              [11, 25, 35, 50, 100, 20000000000000],
              [10, 15, 25, 50, 100, "All"],
            ],
            "font-size": "3rem",
          });
      }
    });
    });
$('#E_situation').select2();
$('#F_situation').select2();
$('#P_situation').select2();
$('#Et_situation').select2();
        });
