# Selligent Content Rendering
Content rendering is used to include Selligent pages inside a client website without using an iFrame. Here is a short description of the inner workings of a Selligent Content Renderer:

![alt text](https://www.longtale.be/imgs/cr_info.png "Selligent Content Rendering Diagram")

1. The user requests a page from the website. This website page contains a Selligent page or form. 
2. The website knows the Selligent hash ID of the webpage/form. It requests the page from the Content renderer with a GET HTTP request containing at least the hash code in its query string. 
3. The hash code is then passed to the SelligentLib component. 
4. The Selligentlib interface component returns all personalized data. The core data contains the page or form. If a form is returned, the form action URI will be of format https://customer_website/[webpage]/?ID=[hash] . So the webpage on the customer website must be able to receive a HTTP POST request. 
5. The Content renderer encapsulates all data into an XML structure. 
6. The XML content is delivered to the website 
7. The website renders the page/form on the user requested page of step 1 

**Now, if the requested page is a Selligent form, additional steps are added:**

8. The user submits the form to https://customer_website/[webpage]/?ID=[hash] via a HTTP POST request. This uri must be configured on the Selligent journey as a content renderer (Advanced properties of the journey) or alternatively, the XML content rendered could configure the content renderer URI dynamically. This way the optiextension.dll that is used by the SelligentLib knows to use this URI as its HTTP POST Form action. 
9. The website receives the HTTP POST form values and aggregates all form values together with the hash ID (passed by the query string in the received HTTP request) into a HTTP GET request to the Content renderer. 
10. The website sends then the HTTP GET or POST request with all aggregated data from step 8 to the XML Content renderer. 
11. The Content renderer passes the received vales to the SelligentLib component. 
12. The Selligentlib component calls the optiextension.dll to receive the data 
13. The Content renderer encapsulates all date into an XML structure. 
14. The XML content is delivered to the website 
15. The page or form is rendered and delivered to the user. If the submitted form contains validation errors, this error can be displayed to the user. Afterwards the described sequence will continue from step 8 
