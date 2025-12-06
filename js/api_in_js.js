// hier die URL fürs lokale Testen ändern
const url = "http://localhost/bibapp_xampp/restAPI.php/";
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
    getProjectData(url + "user").then((data) => {
      console.log(data); // JSON data parsed by `data.json()` call
      let main = document.querySelector("main");
      console.log(main);
      let c = "<ul>";
      data.forEach(d => {
        //hier unten wird ein index mithilfe des Keys des assoziativen Arrays ausgegeben
        c += "<li>" + d.email + "</li>";  
      });
  
      c += "</ul>";
  
      main.innerHTML = c;
    });
  });