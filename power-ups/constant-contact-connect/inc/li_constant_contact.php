<?php

global $cc_exception;

class LI_ConstantContact
{
    var $auth;
    var $api;
    var $debug;
    var $cc_exception;

    /**
    * The ConstantContact contructor
    *
    * @param String $username - Your username
    * @param String $password - Your password
    * @param String $api_key - Your API Key
    * @param Boolean $debug - Whether or not debugging output is displayed
    * @param String $debug_style - Options are "cli" or "html". All it does is print "\n" or "<br>" for debugging output.
    */

    public function __construct ( $username = '', $password = '', $api_key = '', $debug_enabled = FALSE, $debug_style = 'html' )
    {
        $this->debug['enabled'] = $debug_enabled;
        $this->debug['style'] = $debug_style;
        $this->debug['last_response'] = 0;
        $this->auth['username'] = $username;
        $this->auth['password'] = $password;
        $this->auth['api_key'] = $api_key;
        $this->api['url'] = 'https://api.constantcontact.com/ws/customers/'.$username.'/';
        $this->api['inner_url'] = 'http://api.constantcontact.com/ws/customers/'.$username.'/';
        $this->api['relative_url'] = '/ws/customers/'.$username.'/';
    }
    
    //Main Functions

    /**
    * Print debugging output
    *
    * @param String $string - String to print
    */
    private function debug_print($string)
    {
        if($this->debug['enabled'])
        {
            $line_end = "\n";
            if ($this->debug['style'] == 'html')
                $line_end = "<br>";
            print "ConstantContact Debug: $string{$line_end}";
        }
    }

    /**
    * Fetch a URI from ConstantContact
    *
    * @param String $url - URL to connect to
    * @param String $post - POST data to include
    * @param String $fetch_as - Either array or xml for the return object
    * @param String $call_type - NORMAL,PUT,DELETE
    * @return Respose
    */
    private function fetch($url,$post,$fetch_as='array',$call_type='NORMAL')
    {
        $this->debug_print("------------------ fetch ------------------");

        $credentials=$this->auth['api_key'].'%'.$this->auth['username'].':'.$this->auth['password'];

        $this->debug_print("Connecting to '".$url."'");
        $this->debug_print("With Credentials '".$credentials."'");

        $ch=curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD,  $credentials);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION ,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if(strlen(trim($post))>0)
        {
            if ($this->debug['style'] == 'cli')
                $this->debug_print("And Posting:\n\n---START---\n".$post."\n---END---\n\n");
            elseif ($this->debug['style'] == 'html')
                $this->debug_print("And Posting:<pre>\n\n---START---\n".htmlspecialchars($post)."\n---END---\n\n</pre>");
            if ($call_type == 'PUT')
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            elseif ($call_type == 'DELETE')
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            else
                curl_setopt($ch, CURLOPT_POST, 1);
                
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/atom+xml'));
        }
        else
        {
            $this->debug_print("Not posting");
            curl_setopt($ch, CURLOPT_POST, 0);
            if ($call_type == 'PUT')
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            elseif ($call_type == 'DELETE')
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
   
        curl_setopt($ch, CURLOPT_HEADER, 0);


   
        $response = curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->debug_print("HTTP Response Code '".$response_code."'");        
        
        if ( ! in_array($response_code, array('201', '200', '204')) )
        {
            if ( $response_code == 401 )
            {
                $this->cc_exception = "Sorry, that username and password combination isn't right";
            }
            else if ( $response_code == 403 )
            {
                $this->cc_exception = "Your account is locked due to too many bad logins. You can try <a href='https://login.constantcontact.com/login/pwreset.sdo' target='_blank'>resetting your Constant Contact password</a> to unlock your account";
            }
        }

        $this->debug['last_response'] = $response_code;
        
        if ($this->debug['style'] == 'cli')
            $this->debug_print("Received:\n\n---START---\n".$response."\n---END---\n\n");
        elseif ($this->debug['style'] == 'html')
            $this->debug_print("Received:<pre>\n\n---START---\n".htmlspecialchars($response)."\n---END---\n\n</pre>");

        curl_close($ch);

        if ($fetch_as == 'array')
            return $this->xml_to_array($response);
        else
            return $response;
    }
    
    /**
    * Converts XML data into a nested array
    *
    * @param String $contents - XML string
    * @param Integers $get_attributes - Include attributes (default 1)
    * @return Nested associative array
    */
    public function xml_to_array($contents, $get_attributes=1)
    {
        if (!$contents)
            return array();

        if (!function_exists('xml_parser_create'))
        {
            $this->debug_print("ERROR: xml_parser_create function doesn't exist!");
            return array();
        }

        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
        xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
        xml_parse_into_struct( $parser, $contents, $xml_values );
        xml_parser_free( $parser );

        if (!$xml_values)
        {
            $this->debug_print("WARN: Could not parse xml_values!");
            return;
        }

        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array;

        foreach( $xml_values as $data)
        {
            unset($attributes,$value);
            extract($data);

            $result = '';
            if ($get_attributes)
            {
                $result = array();
                if (isset($value))
                    $result['value'] = $value;
                if (isset($attributes))
                {
                    foreach ($attributes as $attr => $val)
                    {
                        if ($get_attributes == 1)
                            $result['attr'][$attr] = $val;
                    }
                }
            }
            elseif (isset($value))
                $result = $value;

            if ($type == "open")
            {
                $parent[$level-1] = &$current;
                if (!is_array($current) || !in_array($tag, array_keys($current)))
                {
                    $current[$tag] = $result;
                    $current = &$current[$tag];
                }
                else
                {
                    if (isset($current[$tag][0]))
                        array_push($current[$tag], $result);
                    else
                        $current[$tag] = array($current[$tag],$result);

                    $last = count($current[$tag]) - 1;
                    $current = &$current[$tag][$last];
                }
            }
            elseif ($type == "complete")
            {
                if (!isset($current[$tag]))
                    $current[$tag] = $result;
                else
                {
                    if ((is_array($current[$tag]) and $get_attributes == 0) || (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1))
                        array_push($current[$tag],$result);
                    else
                        $current[$tag] = array($current[$tag],$result);
                }
            }
            elseif ($type == 'close')
                $current = &$parent[$level-1];
        }
        return($xml_array);
    }
    
    /**
    * Converts id URL to numeric id
    *
    * @param String $url - ID URL
    * @return Numeric ID
    */
    public function id_from_url($url)
    {
        $temp = Array();
        $temp = explode('/',trim($url));
        return trim($temp[count($temp)-1]);
    }
    
    /**
    * Retrieves the XML service document
    *
    * @return XML service document
    */
    public function get_service_doc_xml()
    {
        $url=$this->api_url;
        $post='';
        $response=$this->fetch($url,$post,'xml');
        return $response;
    }

    
    //Contact Functions
    
    /**
    * Formats a contact array into the format we always return as
    * Note:
    * I like to do this because the atom specification has so much extraneous data
    * that I just don't care about. This makes a sane array out of what is
    * incredibly overcomplicated in my opinion.
    * 
    * @param Array - Contact array as returned from fetch/xml_to_array
    * @return Associative array in the format returned by this api
    */
    public function format_contact($response)
    {
        $my_data = Array();
        $my_data = $response['entry']['content']['Contact'];
        $d = Array();
        $d['ContactID'] = $this->id_from_url($my_data['attr']['id']);
        unset($my_data['attr']);
        foreach ($my_data as $dkey => $dval)
        {
            if ($dkey != 'ContactLists')
                $d[$dkey] = $dval['value'];
            else
            {
                $entries = Array();
                if (isset($dval['ContactList']['link']))
                    $entries[] = $dval['ContactList'];
                else
                    $entries = $dval['ContactList'];
                
                foreach ($entries as $entry)
                {
                    $e = Array();
                    $e['ListID'] = $this->id_from_url($entry['attr']['id']);
                    unset($entry['attr']);
                    unset($entry['link']);
                    foreach ($entry as $ekey => $eval)
                        $e[$ekey] = $eval['value'];
                    $d[$dkey][] = $e;                   
                }
            }
        }
        return $d;
    }
    
    /**
    * Return your list of contacts.
    *
    * @param String $key - Optionally specify a key to use for the array
    * @param Boolean $unique - Whether the key you specified is unique
    * @return Associative array containing your contacts indexed by whatever key specified.
    */
    public function get_contacts($key='',$unique=true)
    {
        $ret = Array();
        
        $url = $this->api['url'].'contacts';
        $post = '';
        $response = Array();
        $response = $this->fetch($url,$post);

        $entries = Array();
        if (isset($response['feed']['entry']['link']))
            $entries[] = $response['feed']['entry'];
        else
            $entries = $response['feed']['entry'];
        foreach ($entries as $data)
        {
            $my_data = Array();
            $my_data = $data['content']['Contact'];
            $d = Array();
            $d['ContactID'] = $this->id_from_url($my_data['attr']['id']);
            unset($my_data['attr']);
            foreach ($my_data as $dkey => $dval)
                $d[$dkey] = $dval['value'];
            
            if ($key)
            {
                if ($unique)
                    $ret[$d[$key]] = $d;
                else
                    $ret[$d[$key]][] = $d;
            }
            else
                $ret[] = $d;
        }
        return $ret;
    }
  
    /**
    * Add a contact
    * See Contact Data Format at http://developer.constantcontact.com/doc/contactCollection for all fields
    * 
    * @param Array $data - An associative array containing all the contact data to add
    * @param Array $lists - An array of list_ids to add this contact to
    * @return Integer - ContactID for the new entry
    */
    public function add_contact($data,$lists)
    {
        
        $updated = date('Y-m-d\TH:i:s\Z');
        $xml_string = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><entry xmlns='http://www.w3.org/2005/Atom'></entry>";
        $xml_object = simplexml_load_string($xml_string);
        $xml_object->addChild("title");
        $xml_object->addChild("updated",$updated);
        $author_node = $xml_object->addChild("author");
        $author_node->addChild("name", ("CTCT Samples"));
        $xml_object->addChild("id", 'data:,none');
        $summary_node = $xml_object->addChild("summary");
        $summary_node->addAttribute("type", "text");
        $content_node = $xml_object->addChild("content");
        $content_node->addAttribute("type", "application/vnd.ctct+xml");
        $contact_node = $content_node->addChild("Contact");
        $contact_node->addAttribute("xmlns", "http://ws.constantcontact.com/ns/1.0/");
        $contact_node->addChild("OptInSource", 'ACTION_BY_CUSTOMER');

        foreach ( $data as $key => $val )
        {
            $contact_node->addChild($key, $val);
        }

        $contactlists_node = $contact_node->addChild("ContactLists");
        foreach ( $lists as $list )
        {
            $listNode = $contactlists_node->addChild("ContactList");
            $listNode->addAttribute("id", $list);                  
        }

        $entry = $xml_object->asXML();

        $url= $this->api['url'].'contacts';
    
        $response = Array();
        $response = $this->fetch($url, $entry);
        if ( $this->debug['last_response'] <= 204 )
            return $this->id_from_url($response['entry']['id']['value']);
        else
            return false;
    }

    /**
    * Returns detailed info for a particular contact.
    *
    * @param Integer $id - Contact ID of the contact requested.
    * @return Associative array containing the contact's details
    */
    public function get_contact($id)
    {
        $url = $this->api['url'].'contacts/'.$id;
        $post = '';
        $contact = Array();
        $contact = $this->format_contact($this->fetch($url,$post));
                
        if ($this->debug['last_response'] <= 204)
            return $contact;
        else
            return false;
    }
    
    /**
    * Edit a contact
    * See Contact Data Format at http://developer.constantcontact.com/doc/contactCollection for all fields
    * 
    * @param Integer $id - Contact ID of the contact to edit
    * @param Array $data - An associative array containing all the contact data to edit
    * @return Boolean - True on success and false on failure
    */
    public function edit_contact($id,$data)
    {
        //Get the old xml from get_contact() and then post after replacing values that need replacing
        $url = $this->api['url'].'contacts/'.$id;
        $post = '';
        $post = $this->fetch($url,$post,'xml');
        
        foreach ($data as $key => $val)
            $post = preg_replace("/<$key>.*?<\/{$key}>/s","<$key>$val</$key>",$post);
        
        $this->fetch($url,$post,'array','PUT');
        
        if ($this->debug['last_response'] <= 204)
            return true;
        else
            return false;
    }
    
    /**
    * Delete a contact
    *  
    * @param Integer $id - Contact ID of the contact to delete
    * @return Boolean - True on success and false on failure
    */
    public function delete_contact($id)
    {
        //Get the old xml from get_contact() and then post after replacing values that need replacing
        $url = $this->api['url'].'contacts/'.$id;
        $post = '';
        $this->fetch($url,$post,'array','DELETE');
        
        if ($this->debug['last_response'] <= 204)
            return true;
        else
            return false;
    }
    
    
    /**
    * Search for a contact by email
    * See Contact Data Format at http://developer.constantcontact.com/doc/contactCollection for all fields
    * 
    * @param String $email - Email address of the contact to search for
    * @param Boolean $id_only - If true will return ContactID found otherwise will return all contact info
    * @return Integer of found ContactID or Associative Array of contact info for found contact
    */
    public function search_contact_by_email($email,$id_only=true)
    {
        //Get the old xml from get_contact() and then post after replacing values that need replacing
        $url = $this->api['url'].'contacts?email='.urlencode(strtolower($email));
        $post = '';
        $response = Array();
        $response = $this->fetch($url,$post);
        
        if ($this->debug['last_response'] <= 204)
        {
            if ($id_only)
            {
                return $this->id_from_url($response['feed']['entry']['id']['value']);
            }
            else
            {
                return $this->get_contact($this->id_from_url($response['feed']['entry']['id']['value']));
            }
        }
        else
            return false;
    }
    
    /**
    * Returns contacts updated since date your list of contacts.
    *
    * @param Date $date - Date should be in the format "Y-m-d H:i:s" (this is the datetime mysql spec)
    * @param String $list_type - Type of contact to query for: active,removed,do-not-mail
    * @param String $key - Optionally specify a key to use for the array    
    * @param Boolean $unique - Whether the key you specified is unique
    * @return Associative array containing your contacts indexed by whatever key specified.
    */
    public function search_contacts_by_date($date,$list_type='active',$key='',$unique=true)
    {
        //Format date to UTC atom spec
        $atom_date = date('Y-m-d\TH:i:s\Z',strtotime($date)+date("Z"));
        
        $ret = Array();
        
        $url = $this->api['url'].'contacts?updatedsince='.$atom_date.'&listtype='.$list_type;
        $post = '';
        $response = Array();
        $response = $this->fetch($url,$post);

        $entries = Array();
        if (isset($response['feed']['entry']['link']))
            $entries[] = $response['feed']['entry'];
        else
            $entries = $response['feed']['entry'];
        foreach ($entries as $data)
        {
            $my_data = Array();
            $my_data = $data['content']['Contact'];
            $d = Array();
            $d['ContactID'] = $this->id_from_url($my_data['attr']['id']);
            unset($my_data['attr']);
            foreach ($my_data as $dkey => $dval)
                $d[$dkey] = $dval['value'];
            
            if ($key)
            {
                if ($unique)
                    $ret[$d[$key]] = $d;
                else
                    $ret[$d[$key]][] = $d;
            }
            else
                $ret[] = $d;
        }
        return $ret;
    }
    
    //List Functions
    
    /**
    * Formats a list array into the format we always return as
    * Note:
    * I like to do this because the atom specification has so much extraneous data
    * that I just don't care about it. This makes a sane array out of what is
    * incredibly overcomplicated in my opinion.
    * 
    * @param Array - Contact array as returned from fetch/xml_to_array
    * @return Associative array in the format returned by this api
    */
    public function format_list($response)
    {
        $my_data = Array();
        $my_data = $response['entry']['content']['ContactList'];
        $d = Array();
        $d['ListID'] = $this->id_from_url($my_data['attr']['id']);
        unset($my_data['attr']);
        unset($my_data['Members']);
        foreach ($my_data as $key => $val)
            $d[$key] = $val['value'];
        return $d;
    }
    
    /**
    * Add a list
    * See List Data Format at http://developer.constantcontact.com/doc/contactLists for all fields
    * 
    * @param Array $data - An associative array containing all the contact data to add
    * @return Integer - ListID for the new entry
    */
    public function add_list($data)
    {
        $updated = date('Y-m-d\TH:i:s\Z');
        $post = <<<end
<entry xmlns="http://www.w3.org/2005/Atom">
    <title type="text"> </title>
    <updated>{$updated}</updated>
    <author></author>
    <id>data:,none</id>
    <summary type="text">Contact</summary>
    <content type="application/vnd.ctct+xml">
        <ContactList xmlns="http://ws.constantcontact.com/ns/1.0/">

end;

        foreach ($data as $key => $val)
            $post .= "\t\t\t<$key>{$val}</$key>\n";
        
        $post .= <<<end
        </ContactList>
    </content>
</entry>
end;
    

        $url= $this->api['url'].'lists';
    
        $response = Array();
        $response = $this->fetch($url,$post);
        if ($this->debug['last_response'] <= 204)
            return $this->id_from_url($response['entry']['id']['value']);
        else
            return false;
    }
    
    /**
    * Returns detailed info for a particular list.
    *
    * @param Integer $id - ListID of the list requested.
    * @return Associative array containing the list's details
    */
    public function get_list($id)
    {   
        $url = $this->api['url'].'lists/'.$id;
        $post = '';
        $list = Array();
        $list = $this->format_list($this->fetch($url,$post));
        
        if ($this->debug['last_response'] <= 204)
            return $list;
        else
            return false;
    }
    
    /**
    * Edit a list
    * See List Data Format at http://developer.constantcontact.com/doc/contactLists for all fields
    * 
    * @param Integer $id - List ID of the list to edit
    * @param Array $data - An associative array containing all the list data to edit
    * @return Boolean - True on success and false on failure
    */
    public function edit_list($id,$data)
    {
        //Get the old xml from get_list() and then post after replacing values that need replacing
        $url = $this->api['url'].'lists/'.$id;
        $post = '';
        $post = $this->fetch($url,$post,'xml');
        
        foreach ($data as $key => $val)
            $post = preg_replace("/<$key>.*?<\/{$key}>/s","<$key>$val</$key>",$post);
        
        $this->fetch($url,$post,'array','PUT');
        
        if ($this->debug['last_response'] <= 204)
            return true;
        else
            return false;
    }
    
    /**
    * Return your lists.
    *
    * @param String $key - Optionally specify a key to use for the array
    * @param Boolean $unique - Whether the key you specified is unique
    * @return Associative array containing your lists indexed by whatever key specified.
    */
    public function get_lists($key='',$unique=true)
    {
        $ret = Array();
        
        $url = $this->api['url'].'lists';
        $post = '';
        $response = Array();
        $response = $this->fetch($url,$post);

        $entries = Array();
        if ( isset($response['feed']) )
        {
            if (isset($response['feed']['entry']['link']))
                $entries[] = $response['feed']['entry'];
            else
                $entries = $response['feed']['entry'];
        }
        else
        {
            return array();
        }

        if ( count($entries) )
        {
            foreach ($entries as $data)
            {
                $my_data = Array();
                $my_data = $data['content']['ContactList'];
                $d = Array();
                //$d['ListID'] = $this->id_from_url($my_data['attr']['id']);
                $d['ListID'] = $my_data['attr']['id'];

                // Exclude the default lists like active, do-not-mail and removed
                if ( ! is_numeric($this->id_from_url($my_data['attr']['id'])) )
                    continue;

                unset($my_data['attr']);
                foreach ($my_data as $dkey => $dval)
                {
                    if ( isset($dval['value']) )
                        $d[$dkey] = $dval['value'];
                }
                
                if ($key)
                {
                    if ($unique)
                        $ret[$d[$key]] = $d;
                    else
                        $ret[$d[$key]][] = $d;
                }
                else
                    $ret[] = $d;
            }
            return $ret;
        }
    }

    /**
    * Delete a list
    *  
    * @param Integer $id - ListID of the list to delete
    * @return Boolean - True on success and false on failure
    */
    public function delete_list($id)
    {
        //Get the old xml from get_list() and then post after replacing values that need replacing
        $url = $this->api['url'].'lists/'.$id;
        $post = '';
        $this->fetch($url,$post,'array','DELETE');
        
        if ($this->debug['last_response'] <= 204)
            return true;
        else
            return false;
    }
    
    //Subscription Functions
    
    /**
    * Add a subscription for an existing contact to a list
    * See Contact Data Format at http://developer.constantcontact.com/doc/contactCollection for all fields
    * 
    * @param Integer $contact_id - Contact ID of the contact to use
    * @param Integer $list_id - List ID of the list to add the contact to
    * @param String $optin_source - ACTION_BY_CUSTOMER or ACTION_BY_CLIENT depending on if this person is filling out a form for subscription in realtime
    * @return Boolean - True on success and false on failure
    */
    public function add_subscription($contact_id,$list_id,$optin_source='ACTION_BY_CUSTOMER')
    {
        //Get the old xml from get_contact() and then post after replacing values that need replacing
        $url = $this->api['url'].'contacts/'.$contact_id;
        $post = '';
        $post = $this->fetch($url,$post,'xml');
        
        if (!strstr($post,'</ContactLists>'))
            $post = str_replace('</Contact>',"<ContactLists></ContactLists></Contact>",$post);
            
        if (!strstr($post,'<OptInSource>'))
            $post = str_replace('</Confirmed>',"</Confirmed>\n<OptInSource>$optin_source</OptInSource>",$post);
            
        if (!strstr($post,'<ContactList id="'.$this->api['inner_url'].'lists/'.$list_id.'">'))
            $post = str_replace('</ContactLists>','<ContactList id="'.$this->api['inner_url'].'lists/'.$list_id.'"></ContactList></ContactLists>',$post);

        $this->fetch($url,$post,'array','PUT');
        
        if ($this->debug['last_response'] <= 204)
            return true;
        else
            return false;
    }
    
    /**
    * Remove a subscription for an existing contact to a list
    * See Contact Data Format at http://developer.constantcontact.com/doc/contactCollection for all fields
    * 
    * @param Integer $contact_id - Contact ID of the contact to use
    * @param Integer $list_id - List ID of the list to add the contact to
    * @return Boolean - True on success and false on failure
    */
    public function remove_subscription($contact_id,$list_id)
    {
        //Get the old xml from get_contact() and then post after replacing values that need replacing
        $url = $this->api['url'].'contacts/'.$contact_id;
        $post = '';
        $post = $this->fetch($url,$post,'xml');
        
        $post = preg_replace('/<ContactList id="'.str_replace('/','\/',$this->api['inner_url']).'lists\/'.$list_id.'">.*?<\/ContactList>/s','',$post);

        $this->fetch($url,$post,'array','PUT');
        
        if ($this->debug['last_response'] <= 204)
            return true;
        else
            return false;
    }
    
    //Campaign Functions
    
    /**
    * Formats a campaign array into the format we always return as
    * Note:
    * I like to do this because the atom specification has so much extraneous data
    * that I just don't care about it. This makes a sane array out of what is
    * incredibly overcomplicated in my opinion.
    * 
    * @param Array - Contact array as returned from fetch/xml_to_array
    * @return Associative array in the format returned by this api
    */
    public function format_campaign($response)
    {
        $my_data = Array();
        $my_data = $response['entry']['content']['Campaign'];
        $d = Array();
        $d['CampaignID'] = $this->id_from_url($my_data['attr']['id']);
        $d['FromID'] = $this->id_from_url($my_data['FromEmail']['Email']['attr']['id']);
        $d['FromEmail'] = $my_data['FromEmail']['EmailAddress']['value'];
        unset($my_data['FromEmail']);
        unset($my_data['ReplyToEmail']);
        
        unset($my_data['attr']);
        foreach ($my_data as $dkey => $dval)
        {
            if ($dkey != 'ContactLists')
                $d[$dkey] = $dval['value'];
            else
            {
                $entries = Array();
                if (isset($dval['ContactList']['link']))
                    $entries[] = $dval['ContactList'];
                else
                    $entries = $dval['ContactList'];
                
                foreach ($entries as $entry)
                {
                    $e = Array();
                    $e['ListID'] = $this->id_from_url($entry['attr']['id']);
                    unset($entry['attr']);
                    unset($entry['link']);
                    foreach ($entry as $ekey => $eval)
                        $e[$ekey] = $eval['value'];
                    $d[$dkey][] = $e;                   
                }
            }
        }
        return $d;
    }
    
    /**
    * Return your campaigns.
    *
    * @param String $key - Optionally specify a key to use for the array
    * @param Boolean $unique - Whether the key you specified is unique
    * @return Associative array containing your lists indexed by whatever key specified.
    */
    public function get_campaigns($key='',$unique=true)
    {
        $ret = Array();
        
        $url = $this->api['url'].'campaigns';
        $post = '';
        $response = Array();
        $response = $this->fetch($url,$post);
        
        $entries = Array();
        if (isset($response['feed']['entry']['link']))
            $entries[] = $response['feed']['entry'];
        else
            $entries = $response['feed']['entry'];
        foreach ($entries as $data)
        {
            $my_data = Array();
            $my_data = $data['content']['Campaign'];
            $d = Array();
            $d['CampaignID'] = $this->id_from_url($my_data['attr']['id']);
            unset($my_data['attr']);
            foreach ($my_data as $dkey => $dval)
                $d[$dkey] = $dval['value'];
            
            if ($key)
            {
                if ($unique)
                    $ret[$d[$key]] = $d;
                else
                    $ret[$d[$key]][] = $d;
            }
            else
                $ret[] = $d;
        }
        return $ret;
    }
    
    /**
    * Add a campaign
    * See Campaign Data Format at http://developer.constantcontact.com/doc/manageCampaigns for all fields
    * 
    * @param Array $data - An associative array containing all the campaign data to add
    * @param Array $lists - An array of ListIDs to have this campaign send to
    * @param Array $from_id - FromID to use as the from email address
    * @return Integer - CampaignID for the new entry
    */
    public function add_campaign($data,$lists,$from_id)
    {
        $updated = date('Y-m-d\TH:i:s\Z');
        $post = <<<end
<entry xmlns="http://www.w3.org/2005/Atom">
    <link href="{$this->api['relative_url']}campaigns" rel="edit" />
    <id>{$this->api['inner_url']}campaigns</id>
    <title type="text">{$data['Name']}</title>
    <updated>{$updated}</updated>
    <author>
        <name>Constant Contact</name>
    </author>
    <content type="application/vnd.ctct+xml">
        <Campaign xmlns="http://ws.constantcontact.com/ns/1.0/" id="{$this->api['inner_url']}campaigns/">

end;

        foreach ($data as $key => $val)
        {
            if ($key == 'Date')
                $val = date('Y-m-d\TH:i:s\Z',strtotime($val)+date("Z"));
            elseif ($key == 'EmailContent' || $key == 'StyleSheet' || $key == 'PermissionReminderText')
                $val = urlencode($val);
            elseif ($key == 'TextContent')
                $val = urlencode('<Text>'.$val.'</Text>');
            
            $post .= "\t\t\t<$key>{$val}</$key>\n";
        }
        
        $post .= "\t\t\t<ContactLists>\n";
        foreach ($lists as $list_id)
            $post .= "\t\t\t\t<ContactList id=\"{$this->api['inner_url']}lists/{$list_id}\" />\n";

        $temp = Array();
        $temp = get_from($from_id);
        $email = $temp['EmailAddress'];
        
        $post .= <<<end
            </ContactLists>
            <FromEmail>
                <Email id="{$this->api['inner_url']}emailaddresses/$from_id">
                    <link xmlns="http://www.w3.org/2005/Atom" href="{$this->api['inner_url']}emailaddresses/$from_id" rel="self" />
                </Email>
                <EmailAddress>$email</EmailAddress>
            </FromEmail>
            <ReplyToEmail>
                <Email id="{$this->api['inner_url']}emailaddresses/$from_id">
                    <link xmlns="http://www.w3.org/2005/Atom" href="{$this->api['inner_url']}emailaddresses/$from_id" rel="self" />
                </Email>
                <EmailAddress>$email</EmailAddress>
            </ReplyToEmail>

end;
        
        $post .= <<<end
        </Campaign>
    </content>
</entry>
end;
    

        $url= $this->api['url'].'contacts';
    
        $response = Array();
        $response = $this->fetch($url,$post);
        if ($this->debug['last_response'] <= 204)
            return $this->id_from_url($response['entry']['id']['value']);
        else
            return false;
    }
    
    /**
    * Returns detailed info for a particular campaign.
    *
    * @param Integer $id - Campaign ID of the campaign requested.
    * @return Associative array containing the campaign's details
    */
    public function get_campaign($id)
    {
        $url = $this->api['url'].'campaigns/'.$id;
        $post = '';
        $campaign = Array();
        $campaign = $this->format_campaign($this->fetch($url,$post));
                
        if ($this->debug['last_response'] <= 204)
            return $campaign;
        else
            return false;
    }
    
    /**
    * Edit a campaign
    * See Campaign Data Format at http://developer.constantcontact.com/doc/manageCampaigns for all fields
    * 
    * @param Integer $id - Campaign ID of the list to edit
    * @param Array $data - An associative array containing all the campaign data to edit
    * @return Boolean - True on success and false on failure
    */
    public function edit_campaign($id,$data)
    {
        //Get the old xml from get_campaign() and then post after replacing values that need replacing
        $url = $this->api['url'].'campaigns/'.$id;
        $post = '';
        $post = $this->fetch($url,$post,'xml');
        
        foreach ($data as $key => $val)
            $post = preg_replace("/<$key>.*?<\/{$key}>/s","<$key>$val</$key>",$post);
        
        $this->fetch($url,$post,'array','PUT');
        
        if ($this->debug['last_response'] <= 204)
            return true;
        else
            return false;
    }
    
    /**
    * Delete a campaign
    *  
    * @param Integer $id - CampaignID of the list to delete
    * @return Boolean - True on success and false on failure
    */
    public function delete_campaign($id)
    {
        //Get the old xml from get_campaign() and then post after replacing values that need replacing
        $url = $this->api['url'].'campaigns/'.$id;
        $post = '';
        $this->fetch($url,$post,'array','DELETE');
        
        if ($this->debug['last_response'] <= 204)
            return true;
        else
            return false;
    }
    
    //From Functions
    
    /**
    * Formats a list array into the format we always return as
    * Note:
    * I like to do this because the atom specification has so much extraneous data
    * that I just don't care about. This makes a sane array out of what is
    * incredibly overcomplicated in my opinion.
    * 
    * @param Array - Contact array as returned from fetch/xml_to_array
    * @return Associative array in the format returned by this api
    */
    public function format_from($response)
    {
        $my_data = Array();
        $my_data = $response['entry']['content']['Email'];
        $d = Array();
        $d['FromID'] = $this->id_from_url($my_data['attr']['id']);
        unset($my_data['attr']);
        foreach ($my_data as $key => $val)
            $d[$key] = $val['value'];
        return $d;
    }
    
    /**
    * Returns detailed info for a particular list.
    *
    * @param Integer $id - FromID of the from email requested.
    * @return Associative array containing the from's details
    */
    public function get_from($id)
    {   
        $url = $this->api['url'].'settings/emailaddresses/'.$id;
        $post = '';
        $from = Array();
        $from = $this->format_from($this->fetch($url,$post));
        
        if ($this->debug['last_response'] <= 204)
            return $from;
        else
            return false;
    }
    
    /**
    * Return your from email addresses.
    *
    * @param String $key - Optionally specify a key to use for the array
    * @param Boolean $unique - Whether the key you specified is unique
    * @return Associative array containing your lists indexed by whatever key specified.
    */
    public function get_froms($key='',$unique=true)
    {
        $ret = Array();
        
        $url = $this->api['url'].'settings/emailaddresses';
        $post = '';
        $response = Array();
        $response = $this->fetch($url,$post);

        $entries = Array();
        if (isset($response['feed']['entry']['link']))
            $entries[] = $response['feed']['entry'];
        else
            $entries = $response['feed']['entry'];
        foreach ($entries as $data)
        {
            $my_data = Array();
            $my_data = $data['content']['Email'];
            $d = Array();
            $d['FromID'] = $this->id_from_url($my_data['attr']['id']);
            unset($my_data['attr']);
            foreach ($my_data as $dkey => $dval)
                $d[$dkey] = $dval['value'];
            
            if ($key)
            {
                if ($unique)
                    $ret[$d[$key]] = $d;
                else
                    $ret[$d[$key]][] = $d;
            }
            else
                $ret[] = $d;
        }
        return $ret;
    }

}

?>
