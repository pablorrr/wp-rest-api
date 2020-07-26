var portfolioPostsBtn = document.getElementById("portfolio-posts-btn");
var portfolioPostsContainer = document.getElementById("portfolio-posts-container");


//create ajax posts loading

if (portfolioPostsBtn) {
  portfolioPostsBtn.addEventListener("click", function() {
    var ourRequest = new XMLHttpRequest();//create AJAX OBJECT
	//check functions.php magicalData definied
    ourRequest.open('GET', magicalData.siteURL);//open json link //dynamically
    ourRequest.onload = function() {//when page is load run this code
      if (ourRequest.status >= 200 && ourRequest.status < 400) {//chcekc theres no problem with request
        var data = JSON.parse(ourRequest.responseText);//taken data are string looks like JSON so string //must be transform into readable JSON format that can read value of a key, check url:
		//https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/JSON/parse
        createHTML(data);//output data on front page
        portfolioPostsBtn.remove();//remove button afterr sending posts on front page
      } else {//when error occurs
        console.log("We connected to the server, but it returned an error.");
      }
    };//finish onload function
	
    //fired on console when error occurs
    ourRequest.onerror = function() {
      console.log("Connection error");
    };
   /* send AJAX request */
    ourRequest.send();
  });
}

function createHTML(postsData) {//postdata as data alias
  var ourHTMLString = '';//for concat puposes
  for (i = 0; i < postsData.length; i++) {//iterata all postdata objects
  
  //title.rendered - taken from page  localhost//domainname_or_wpinstall_folder/wp-json/wp/v2/posts 

    ourHTMLString += '<h2>' + postsData[i].title.rendered + '</h2>';
	
	//content.rendered; - taken from page  localhost//domainname_or_wpinstall_folder/wp-json/wp/v2/posts 
    ourHTMLString += postsData[i].content.rendered;
  }
  portfolioPostsContainer.innerHTML = ourHTMLString;//write to HTML container
}


// Quick Add Post AJAX

var quickAddButton = document.querySelector("#quick-add-button");

if (quickAddButton) {//check is browser handles JS
  quickAddButton.addEventListener("click", function() {
    var ourPostData = {//create JSON object
      "title": document.querySelector('.admin-quick-add [name="title"]').value,//get title value
      "content": document.querySelector('.admin-quick-add [name="content"]').value,//get content value
      "status": "publish"//set up post status as published
    }

    var createPost = new XMLHttpRequest();//create AJAX JS object
	//data are sending not retriving thats why POST as method
    createPost.open("POST", magicalData.siteURL);//check functions.php
	
	/* set up document header with nonce to security reasons- to be sure data are send from proper page */
    createPost.setRequestHeader("X-WP-Nonce", magicalData.nonce);
	
	/* set up doc header content type */
    createPost.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
	//https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/JSON/stringify
	//JSON.stringify converts JSON FORMAT into string
    createPost.send(JSON.stringify(ourPostData));//send data (posts) to admin and database
    createPost.onreadystatechange = function() {
      if (createPost.readyState == 4) {//chceck its ok
        if (createPost.status == 201) {//check is request ok
		//make field empty when post passed
          document.querySelector('.admin-quick-add [name="title"]').value = '';
		//make field empty when post passed
          document.querySelector('.admin-quick-add [name="content"]').value = '';
        } else {//when errors occurs
          alert("Error - try again.");
        }
      }
    }
  });
  console.log(magicalData.siteURL);
}