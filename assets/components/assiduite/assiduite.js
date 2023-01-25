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

           
  $(".loader").hide();
  // $("#etudiant_det_modal").hide();
/////////////////////////////////// datatable //////////////////////////

        var tableData = [];

        function seanceaffichage(var1, var2, var3) {
        $(".loader").show();

          $.ajax({
            type: "POST",
            url: "/api/Seance_aff/" + var1 + "/" + var2 + "/" + var3,
            success: function (html) {
        $(".loader").hide();
              
              if ($.fn.DataTable.isDataTable("#dtDynamicVerticalScrollExample")) {
                $("#dtDynamicVerticalScrollExample").DataTable().clear().destroy();
              }
              $("#dtDynamicVerticalScrollExample")
                .html(html)
                .DataTable({
                  bLengthChange: false,
                  lengthMenu: [
                    [11, 25, 35, 50, 100, 20000000000000],
                    [10, 15, 25, 50, 100, "All"],
                  ],
                  "font-size": "3rem",
                });
            },
          });
          return var1;
        }

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
////////////////////////////////////////////////////////////////

          function highlight() {}
          $("#dtDynamicVerticalScrollExample").DataTable({
            bLengthChange: false,
            lengthMenu: [
              [13, 25, 35, 50, 100, 20000000000000],
              [10, 15, 25, 50, 100, "All"],
            ],
          });
          
          $("#dtDynamicVerticalScrollExample_pointeuse").DataTable({
            bLengthChange: false,
            lengthMenu: [
              [13, 25, 35, 50, 100, 20000000000000],
              [10, 15, 25, 50, 100, "All"],
            ],
          });
          $("#dtDynamicVerticalScrollExample_pointeuse2").DataTable({
            bLengthChange: false,
            lengthMenu: [
              [13, 25, 35, 50, 100, 20000000000000],
              [10, 15, 25, 50, 100, "All"],
            ],
          });
          $("#dtDynamicVerticalScrollExample_situation").DataTable({
            bLengthChange: false,
            lengthMenu: [
              [13, 25, 35, 50, 100, 20000000000000],
              [10, 15, 25, 50, 100, "All"],
            ],
          });

          $("#dtDynamicVerticalScrollExample2").DataTable({
            bLengthChange: false,
          });

          $(".dataTables_length").addClass("bs-select");
  ////////////////  //////////////////////////
//////////////////////////////// dropdown //////////////////////////

  $("#etablissement").prop("selectedIndex", 1);
  $("#formation").prop("selectedIndex", 1);
  $("#promotion").prop("selectedIndex", 1);
  // -------------------------------------------------
/////////////////////////////////dropdown-situation////////////////////////////
  $("#E_situation").prop("selectedIndex", 1);
  $("#F_situation").prop("selectedIndex", 1);
  $("#P_situation").prop("selectedIndex", 1);

/////////////////////////////////////////////etablissement//////////

            $("#etablissement").on("change", function () {
              var etablissement = $(this).val();
              $.ajax({
                type: "POST",
                url: "/api/Formation_aff/" + etablissement,
                success: function (html) {
                  $("#formation").html(html);
                  $("#formation").prop("selectedIndex", 1);

                  $.ajax({
                    type: "POST",
                    url: "/api/Promotion_aff/" + $("#formation").val(),
                    success: function (html) {
                      $("#promotion").html(html);
                      $("#promotion").prop("selectedIndex", 1);
                      seanceaffichage($("#promotion").val(), $("#datetime").val(),'CR');
                    },
                  });
                },
              });
            });
///////////////////////////////////////////////Fomation//////////

  $("#formation").on("change", function () {
    var formation = $(this).val();
    $.ajax({
      type: "POST",
      url: "/api/Promotion_aff/" + formation,
      success: function (html) {
        $("#promotion").html(html);
        $("#promotion").prop("selectedIndex", 1);
        seanceaffichage($("#promotion").val(), $("#datetime").val(),'CR');
      },
    });
  });
////////////////////////////////////////////////Promotion//////////

          $("#promotion").on("change", function () {
            var promotion = $(this).val();
            seanceaffichage(promotion, $("#datetime").val(),'CR');
          });
////////////////////////////////////////////////Date//////////

  $("#datetime").on("change", function () {
    var date = $(this).val();
    seanceaffichage($("#promotion").val(), date,'CR');
  });

  ///////////////////////////////////////////// date //////////////////////////

  var now = new Date();
  var day = ("0" + now.getDate()).slice(-2);
  var month = ("0" + (now.getMonth() + 1)).slice(-2);
  var today = now.getFullYear() + "-" + month + "-" + day;

  $("#datetime").val(today);
  var promotion = $("#promotion").val();
  let list = [];
  seanceaffichage(promotion, today,'CR');


  $("body #dtDynamicVerticalScrollExample").on("click", "tr", function () {
    var selected = $(this).hasClass("highlighty");
    $("body #dtDynamicVerticalScrollExample tr").removeClass("highlighty");
    $("body #dtDynamicVerticalScrollExample tr").removeClass("odd");
    $("body #dtDynamicVerticalScrollExample tr").removeClass("even");

    if (!selected) {
      $(this).addClass("highlighty");
      var currentRow = $(this).closest("tr");
      var statut = currentRow.find("td:eq(1)").html();
      list = [];
      list.push({
        promotion: currentRow.find("td:eq(2)").html(),
        seance: currentRow.find("td:eq(3)").html(),
        groupe: currentRow.find("td:eq(10)").html(),
        hd: currentRow.find("td:eq(8)").html(),
        module: currentRow.find("td:eq(14)").html(),
        sale: currentRow.find("td:eq(15)").html(),
        existe: currentRow.find("td:eq(11)").html(),
        statut: currentRow.find("td:eq(1)").html(),
      });
      $("#traite_epreuve").hide();
      $("#retraiter_seance").hide();
      $("#deverouiller-modal").hide();
      $("#verouiller-modal").hide();
      $("#assiduite_print").hide();
      if (statut == '1') {
        $("#traite_epreuve").css({ "display": "none" });
        $("#retraiter_seance").show();
        $("#verouiller-modal").show();
        $("#assiduite_print").show();
      }
      if (statut == '2') {
        $("#deverouiller-modal").show();
        $("#assiduite_print").show();
      } else {
        $("#traite_epreuve").show();
      }
    }
    if(statut == '1' || statut == '2'){
    list.forEach((obj) => {

    $.ajax({
      type: "POST",
      url: "/api/count_seance/"+obj.seance,
      data: {
       
        seance: obj.seance,
        
      },
      success: function (html) {
        $(".grid").html(html);

      }
  });
  });
}
console.log(list);

  });

  ////////////////////////////////:: pop up etudiant ////////////////////////////////////:
  $("body #dtDynamicVerticalScrollExample").on("dblclick", "tr", function () {
    $("#etudiant-modal").modal("toggle");
    $("#etudiant-modal").modal("show");
    list.forEach((obj) => {
      $.ajax({
        type: "POST",
        url: "/api/Etud_aff",
        data: {
          promotion: obj.promotion,
          seance: obj.seance,
          groupe: obj.groupe,
          existe: obj.existe,
        },
        success: function (html) {
          if ($.fn.DataTable.isDataTable("#dtDynamicVerticalScrollExample2")) {
            $("#dtDynamicVerticalScrollExample2").DataTable().clear().destroy();
          }
          $("#dtDynamicVerticalScrollExample2")
            .html(html)
            .DataTable({
              bLengthChange: false,
              lengthMenu: [
                [25, 50, 75, 100, 125, 20000000000000],
                [10, 15, 25, 50, 100, "All"],
              ],
            });
        },
      });
    });
  });
////////////////////////////////:: traitement ////////////////////////////////////:
  $("body #traite_epreuve").on("click", function () {

    list.forEach((obj) => {
      if (obj.groupe === "") {
        obj.groupe = "empty";
      }
      if ( obj.statut != '1'){
      $(".loader").show();

      $.ajax({
        type: "POST",
        url: "/api/traitement_assiduite",
        data: {
          // promotion: obj.promotion,
          seance: obj.seance,
          date: $("#datetime").val(),
          // hd: obj.hd,
          // module: obj.module,
          // groupe: obj.groupe,
          // sale: obj.sale,
          type : 'traite',
        },
        success: function (html) {
        $(".loader").hide();

          seanceaffichage($("#promotion").val(), $("#datetime").val(),'CR');
          Toast.fire({
            icon: 'success',
            title: 'seance traité avec succes',
        })
          $(".grid").html(html);
          $("#traite_epreuve").hide();
          $("#retraiter_seance").hide();
          $("#deverouiller-modal").hide();
          $("#verouiller-modal").hide();
          $("#assiduite_print").hide();
          $("#retraiter_seance").show();
          $("#verouiller-modal").show();
          $("#assiduite_print").show();
        },
      });
    }
    else{
      Toast.fire({
        icon: 'error',
        title: 'seance deja traité',
    })
    }

    });
  });

  ////////////////////////////////:: retraiter  ////////////////////////////////////:

  $("body #retraiter_seance").on("click", function () {
    list.forEach((obj) => {
      if (obj.groupe === "") {
        obj.groupe = "empty";
      }
      $.ajax({
        type: "POST",
        url: "/api/traitement_assiduite",
        data: {
          // promotion: obj.promotion,
          seance: obj.seance,
          date: $("#datetime").val(),
          // hd: obj.hd,
          // module: obj.module,
          // groupe: obj.groupe,
          // sale: obj.sale,
          type : 'retraite',
        },
        success: function (html) {
          seanceaffichage($("#promotion").val(), $("#datetime").val(),'CR');
          $(".grid").html(html);
          $("#traite_epreuve").hide();
          $("#retraiter_seance").hide();
          $("#deverouiller-modal").hide();
          $("#verouiller-modal").hide();
          $("#assiduite_print").hide();
          $("#retraiter_seance").show();
          $("#verouiller-modal").show();
          $("#assiduite_print").show();
        },
      });
    });
  });

  ////////////////////////////////:: feuile pdf  ////////////////////////////////////:
  $("body #assiduite_print").on("click", function () {
    list.forEach((obj) => {

    window.open('/assiduite/assiduites/pdf/'+obj.seance, '_blank');

});
});

//   ////////////////////////////////::  ////////////////////////////////////:
//   ////////////////////////////////:: situation presentil pdf  ////////////////////////////////////:
//   $("body #situation_presentiel").on("click", function () {
//     // list.forEach((obj) => {
//       var etudiant = $("#Et_situation").val();
//       // var date_debut = $("#datetimeDsituation").val();
//       // var date_fin = $("#datetimeFsituation").val();

//     window.open('/assiduite/assiduites/pdf_presentiel/'+etudiant, '_blank');

// // });
// });

  ////////////////////////////////::  ////////////////////////////////////:
  ////////////////////////////////:: remove seance   ////////////////////////////////////:
  $("body #remove").on("click", function () {
    list.forEach((obj) => {

      $.ajax({
        type: "POST",
        url: "/api/remove_seance/"+obj.seance,
        data: {
          seance: obj.seance,
         
        },
        success: function (html) {
          seanceaffichage($("#promotion").val(), $("#datetime").val(),'CR');
        
        },
      });
  
  });
   
});

  ////////////////////////////////:: existe   ////////////////////////////////////:
  $("body #existe").on("click", function () {
    list.forEach((obj) => {

      $.ajax({
        type: "POST",
        url: "/api/exist_seance/"+obj.seance,
        data: {
          seance: obj.seance,
         
        },
        success: function (html) {
          seanceaffichage($("#promotion").val(), $("#datetime").val(),'CR');
        
        },
      });
  
  });
   
});

  ////////////////////////////////:: sign   ////////////////////////////////////:
  $("body #sign").on("click", function () {
    list.forEach((obj) => {

      $.ajax({
        type: "POST",
        url: "/api/sign_seance/"+obj.seance,
        data: {
          seance: obj.seance,
         
        },
        success: function (html) {
          Toast.fire({
            icon: 'success',
            title: 'seance signé',
        })
          seanceaffichage($("#promotion").val(), $("#datetime").val(),'CR');
        
        },
      });
  
  });
  
});

  ////////////////////////////////:: cancel   ////////////////////////////////////:
  $("body #cancel").on("click", function () {
    list.forEach((obj) => {

      $.ajax({
        type: "POST",
        url: "/api/cancel_seance/"+obj.seance,
        data: {
          seance: obj.seance,
         
        },
        success: function (html) {
          seanceaffichage($("#promotion").val(), $("#datetime").val(),'CR');
        
        },
      });
  
  });
   
});

  ////////////////////////////////::  ////////////////////////////////////:
  ////////////////////////////////:: deverou  ////////////////////////////////////:
  $("body #deverouiller-modal").on("click", function () {
    list.forEach((obj) => {

      $.ajax({
        type: "POST",
        url: "/api/dever_seance/"+obj.seance,
        data: {
          seance: obj.seance,
         
        },
        success: function (html) {
          seanceaffichage($("#promotion").val(), $("#datetime").val(),'CR');
        
        },
      });
  
  });
   
});
  ////////////////////////////////:: modifier_salle  ////////////////////////////////////:
  $("body #modisalle").on("click", function () {
    var salle = $("#salle").val();
    
    list.forEach((obj) => {

      $.ajax({
        type: "POST",
        url: "/api/modifier_salle/"+obj.seance+"/"+salle,
        data: {
          seance: obj.seance,
         
        },
        success: function (html) {
          seanceaffichage($("#promotion").val(), $("#datetime").val(),'CR');
        
        },
      });
  
  });
   
});
  ////////////////////////////////:: modifier_salle  ////////////////////////////////////:
  $("body #verouiller-modal").on("click", function () {
    
    list.forEach((obj) => {

      $.ajax({
        type: "POST",
        url: "/api/lock_seance/"+obj.seance,
        data: {
          seance: obj.seance,
         
        },
        success: function (html) {
          seanceaffichage($("#promotion").val(), $("#datetime").val(),'CR');
        
        },
      });
  
  });
   
});

//////////////////////////////// parlot ////////////////////////////////////
function selects(){  
  var ele=document.getElementsByName('chk');  
  for(var i=0; i<ele.length; i++){  
      if(ele[i].type=='checkbox')  
          ele[i].checked=true;  
  }  
}  
function deSelect(){  
  var ele=document.getElementsByName('chk');  
  for(var i=0; i<ele.length; i++){  
      if(ele[i].type=='checkbox')  
          ele[i].checked=false;  
        
  }  
}          
$("body #check").on("click", function () {
alert('ok');
selects();  // $("#parlot_modal").show();
 
});
$("body #uncheck").on("click", function () {
alert('ok');
deSelect();  // $("#parlot_modal").show();
 
});
  ////////////////////////////////::  ////////////////////////////////////:
//////////////////////////////// parlot_hd-f ////////////////////////////////////

$("body #parlot_search").on("click", function () {
    
  var hd = $("#hd").val();
  var hf = $("#hf").val();
  $.ajax({
    type: "POST",
    url: "/api/parlot",
    data: {
      hd: hd,
      hf: hf,
     
    },
    success: function (html) {
      if ($.fn.DataTable.isDataTable("#parlot_datatable")) {
        $("#parlot_datatable").DataTable().clear().destroy();
      }
      $("#parlot_datatable")
        .html(html)
        .DataTable({
          bLengthChange: false,
          lengthMenu: [
            [11, 25, 35, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
          ],
          "font-size": "3rem",
        });
    },
  });
 
});
//////////////////////////////// parlot_traitement ////////////////////////////////////

$("body #parlot_traiter").on("click", async function () {
 
  let result;
      var val = [];
      $(':checkbox:checked').each(function(i){
        val[i] = $(this).val();
      });
      for(let value of val){
    try {
      // const request = await axios.post('/administration/epreuve/import', {
      //   seance: value,
      //   date: $("#datetime").val(),
      //   type : 'traite',
      // });

      result = await $.ajax({
        type: "POST",
        url: "/api/traitement_assiduite",
        data: {
          seance: value,
          date: $("#datetime").val(),
          type : 'traite',
        },
//         success: function (html) {
// alert(html);
//     // window.open('/assiduite/assiduites/pdf/'+html, '_blank');
//   },

      });
 window.open('/assiduite/assiduites/pdf/'+result, '_blank');
} catch (error) {
      console.error(error);
  }
      }
  
  ////////////////////////////////////////////////////////////////////:
});



// $("body #situation_search").on("click", function () {
// etudiant = $("#Et_situation").val();
// dated = $("#datetimeDsituation").val();
// datef = $("#datetimeFsituation").val();
// $.ajax({
//   type: "POST",
//   url: "/api/aff_situation",
//   data: {
//     etudiant : etudiant,
//     dated : dated,
//     datef : datef ,
//   },
//   success: function (html) {
//     if ($.fn.DataTable.isDataTable("#dtDynamicVerticalScrollExample_situation")) {
//       $("#dtDynamicVerticalScrollExample_situation").DataTable().clear().destroy();
//     }
//     $("#dtDynamicVerticalScrollExample_situation")
//       .html(html)
//       .DataTable({
//         bLengthChange: false,
//         lengthMenu: [
//           [11, 25, 35, 50, 100, 20000000000000],
//           [10, 15, 25, 50, 100, "All"],
//         ],
//         "font-size": "3rem",
//       });
//   }
// });
// });

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


 
            
//  //////////////////extraction////////////////:
//  $('#create_extraction').click(function(){ 

//   var to = $('#datetimeFsituation').val();
//   var from = $('#datetimeDsituation').val();
//   var service = $('#E_situation').val();
//   var formation = $('#F_situation').val();
//   var promotion = $('#P_situation').val();


//   var tou =  $('input[name="tous"]:checked').val();
  
//           // window.location.href = "{{ path('extraction') }}?To="+to+"&From="+from;
//          url = "/api/generate_extraction?To="+to+"&From="+from+"&formation="+formation+"&promotion="+promotion+"&Service="+service+"&Tou="+tou+"&type=normal";;
//          window.open(url);
           

//             });        
            
 //////////////////extraction stage////////////////:
 $('#create_extraction_stage').click(function(){ 

  var to = $('#datetimeFsituation').val();
  var from = $('#datetimeDsituation').val();
  var service = $('#E_situation').val();
  var formation = $('#F_situation').val();
  var promotion = $('#P_situation').val();


  var tou =  $('input[name="tous"]:checked').val();
  
          // window.location.href = "{{ path('extraction') }}?To="+to+"&From="+from;
         url = "/api/generate_extraction?To="+to+"&From="+from+"&formation="+formation+"&promotion="+promotion+"&Service="+service+"&Tou="+tou+"&type=stage";
         service;
         window.open(url);
           

            });        
  //////////////////////////etudiant details ////////////////////////////////////////////

  $("body #dtDynamicVerticalScrollExample2").on("dblclick", "tr", function () {
   
    // alert(statut);
     list.forEach((obj) => {
    
       if (obj.statut == 1) {
        $("#etudiant_det_modal").modal("toggle");
        $("#etudiant_det_modal").modal("show");
        var row_etudiant = $(this).closest("tr");
        var id_etudiant = row_etudiant.find("td:eq(0)").html();
        $.ajax({
          type: "POST",
          url: "/api/Etud_details",
          data: {
            etudiant: id_etudiant,
            seance: obj.seance
            
          },
          success: function (html) {
            $('#modal_etud_det').html(html);
          
          },
        });
       }

     
     
    });
  });


  //////////////////////////valider etudiant details ////////////////////////////////////////////

  $("body #save_etud_det").on("click", function () {
    let justif = 0;
    if ($('input.justifier').is(':checked')) {
      justif = 1;

    }

    $.ajax({
      type: "POST",
      url: "/api/Etud_details_valide",
      data: {
        etudiant: $('#ID_Admission').val(),
        seance: $('#Id_Seance').val(),
        cat_ens: $('#Categorie_ens').val(),
        motif_abs: $('#motif_abs').val(),
        obs: $('#obs').val(),
        justif: justif,
        
      },
      success: function (html) {
        list.forEach((obj) => {
          $.ajax({
            type: "POST",
            url: "/api/Etud_aff",
            data: {
              promotion: obj.promotion,
              seance: obj.seance,
              groupe: obj.groupe,
              existe: obj.existe,
            },
            success: function (html) {
              if ($.fn.DataTable.isDataTable("#dtDynamicVerticalScrollExample2")) {
                $("#dtDynamicVerticalScrollExample2").DataTable().clear().destroy();
              }
              $("#dtDynamicVerticalScrollExample2")
                .html(html)
                .DataTable({
                  bLengthChange: false,
                  lengthMenu: [
                    [25, 50, 75, 100, 125, 20000000000000],
                    [10, 15, 25, 50, 100, "All"],
                  ],
                });
            },
          });
        });
        $("#etudiant_det_modal").modal("toggle");
        $("#etudiant_det_modal").modal("hide");
      
      },
    });
  });

  //////////////////////////Pointage ////////////////////////////////////////////

  $("body #pointage").on("click", function () {

list.forEach((obj) => {
  if (obj.statut == 1) {

$.ajax({
  type: "POST",
  url: "/api/Etud_pointage",
  data: {
    promo: $('#promotion').val(),
    date: $('#datetime').val(),
    hd: obj.hd,
  },
  success: function (html) {
    if ($.fn.DataTable.isDataTable("#dtDynamicVerticalScrollExample4")) {
      $("#dtDynamicVerticalScrollExample4").DataTable().clear().destroy();
    }
    $("#dtDynamicVerticalScrollExample4")
      .html(html)
      .DataTable({
        bLengthChange: false,
        lengthMenu: [
          [25, 50, 75, 100, 125, 20000000000000],
          [10, 15, 25, 50, 100, "All"],
        ],
      });
  },
});
}
  });
  });


  ///////////////////////////////////////////////////////////////////////////////////////

$('#E_situation').select2();
$('#F_situation').select2();
$('#P_situation').select2();
$('#Et_situation').select2();
});
