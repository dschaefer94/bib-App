// hier die URL fürs lokale Testen ändern
const url = "http://localhost/2024_EIA/taskit_vorlage/restAPI.php/";
async function getProjectData(url) {
    // Default options are marked with *
    const response = await fetch(url, {
      method: "GET", // *GET, POST, PUT, DELETE, etc.
    });
    return response.json(); // parses JSON response into native JavaScript objects
  }
  let linkElement = document.querySelector("#showProjects");
  linkElement.addEventListener("click", e => {
    e.preventDefault();
    getProjectData(url + "project").then((data) => {
      console.log(data); // JSON data parsed by `data.json()` call
      let main = document.querySelector("main");
      console.log(main);
      let c = "<ul>";
      data.forEach(d => {
        c += "<li>" + d.name + "</li>";  
      });
  
      c += "</ul>";
  
      main.innerHTML = c;
    });
  });