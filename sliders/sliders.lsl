string ourURL = "undefined";

vector color;


receive (string body, string method, key id) {
    string query;
    if (method == "POST")   query = body;
    if (method == "GET")    query = llGetHTTPHeader (id, "x-query-string");
    llHTTPResponse(id, 200, "Ok");

    llSetText (query, <0,1,0>, 1.0);

    // Parse the request

    list parse = llParseString2List (query, ["&","="], []);
    string  param = llList2String (parse, 0);
    float   value = llList2Float  (parse, 1);

    // Do something with the data
    
    if (param == "Param1") color.x = value;
    if (param == "Param2") color.y = value;
    if (param == "Param3") color.z = value;

    llSetColor (color, ALL_SIDES); 
}





default {

    state_entry() {
        llRequestURL();
        llSetTimerEvent (1.0);
    }

    http_request(key id, string method, string body) {
        if (method == URL_REQUEST_GRANTED) {
            ourURL = body;
            llOwnerSay ("Copy this to the test client\n"+ourURL);
        } else
            receive (body, method, id);
    }

}