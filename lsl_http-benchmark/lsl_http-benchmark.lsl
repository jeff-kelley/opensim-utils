string ourURL = "undefined";

integer received_seqnum;
integer expected_seqnum;
integer requestsCount;

receive (string body, string method, key id) {
    string query;
    if (method == "POST")   query = body;
    if (method == "GET")    query = llGetHTTPHeader (id, "x-query-string");
    llHTTPResponse(id, 200, "Ok");

    llSetText (query, <0,1,0>, 1.0);

    // Parse the request

    list parse = llParseString2List (query, ["&","="], []);
    string  param = llList2String (parse, 0);
    integer value = llList2Integer(parse, 1);

    // Check sequence number

    received_seqnum = value;

    if (received_seqnum == 1) expected_seqnum = 1; // Start of test
    
    if (received_seqnum != expected_seqnum) {
        llSay (0, "Sequence error");
    }

    expected_seqnum = received_seqnum + 1;
    requestsCount++;
}


dostats () {
    if (requestsCount == 0) return;
    llOwnerSay ("Requests/second : "+(string)requestsCount);
    requestsCount = 0;
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

    timer() {
        dostats();
    }

}