$(document).ready(function() {
  $("#input").submit(function(event) {
    event.preventDefault();
    // gather form data
    var input = { search: $("#search").val(),
                  sort: $("#sort").val()
                };
    // error handling
    if (!input.search || input.search.length === 0) {
      $("#results").html('<p style="color:red">Please enter a search term</p>');
      return;
    }
    if (!input.sort || input.sort.length === 0) {
      $("#results").html('<p style="color:red">Please choose a field to sort by</p>');
      return;
    }
    // post form data to php script
    $.ajax({
      type: "POST",
      url: "/ajax/getCountries.php",
      data: input,
      dataType: "json",
      error: function() {
        $("#results").html('<p style="color:red">No results found for that search term</p>');
      },
      success: function(resp) {

        // build out html for table
        var table = "<table><tr>";
        resp.headers.forEach(function(element) {
          table += "<th>"+element+"</th>";
        });
        table += "</tr>";

        resp.body.forEach(function(row) {
          table += "<tr>";
          row.forEach(function(cell) {
            // find our image source URLs and provide appropriate html
            if (String(cell).includes("https://")) {
              table += '<td class="img"><img src="'+cell+'" /></td>';
            }
            else {
              table += "<td>"+cell+"</td>";
            }
          });
          table += "</tr>";
        });

        table += "</table>";

        var metadata = "<p>Total number of countries returned by API: "+resp.numCountries+"</p>";

        var list = "";
        var subregionCount = 0;
        // iterate over regions
        Object.keys(resp.regions).forEach(function(region) {

          list += "<li><b>"+region+"</b><ul>";
          var regionTotal = 0;
          // iterate over subregions
          Object.keys(resp.regions[region]).forEach(function(subregion) {
            
            list += "<li>"+subregion+": "+resp.regions[region][subregion]+"</li>";
            // tally up our countries in each region/subregion
            subregionCount++;
            regionTotal += resp.regions[region][subregion];
          });
          list += "</li><li><b>Total: "+regionTotal+"</b></li></ul>";
        });
        list += "</ul>";

        metadata += "<p>Number of countries from each region ("+Object.keys(resp.regions).length+
          " regions total) and subregion ("+subregionCount+" subregions total) present in results:<ul>";

        metadata += list;

        $("#results").html(table+metadata);
      }
    });
  });
});
