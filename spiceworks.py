# Unofficial Spiceworks API Python Implementaiton
# Author: Sam Woodhead (sam@blueforge.xyz)

import json
import requests

class SpiceworksAPI(object):
    """Represents an implementation of the spiceworks API"""

    def __init__(self, usr, pwd, site='http://spiceworks.example.com', loginRoute='/login', logoutRoute='/logout'):
        self.site = site
        self.loginRoute = loginRoute
        self.logoutRoute = logoutRoute
        self.headers = {
            'Connection': 'keep-alive',
            'Cache-Control': 'max-age=0',
            'Origin': site,
            'Upgrade-Insecure-Requests': '1',
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
            'Referer': site + loginRoute,
            'Accept-Encoding': 'gzip, deflate',
            'Accept-Language': 'en-GB,en-US;q=0.9,en;q=0.8,fr;q=0.7',
        }
        self.session = self.StartSession(usr, pwd)
        return

    def StartSession(self, usr, pwd):
        """Create a new API session"""
        # Visit the login page to get the authentication token
        s = requests.session()
        s.headers = self.headers
        r = s.get(self.site + self.loginRoute)
        authIndex = r.text.index('authenticity_token')

        # Parse the form data and build the authentication token
        snippet = r.text[authIndex:authIndex+100]
        authToken = ''

        for char in snippet[snippet.index('value=\"') + len('value=\"'):]:
            if char == '"':
                break
            else:
                authToken += char

        data = {
            'authenticity_token': authToken,
            '_pickaxe': '\u2E15',
            'pro_user[email]': usr,
            'pro_user[password]': pwd,
            'pro_user[remember_me]': '0'
        }

        # Login with post request
        r = s.post(self.site + self.loginRoute, data=data)
        return s

    def EndSession(self):
        """Logout from the session"""
        return self.session.get(self.site + self.logoutRoute)

    def GetTicketJSON(self, ticketId):
        """Get the json of a ticket (passed as integer ticket number)"""
        route = '/api/bbtickets/' + str(ticketId)
        r = self.session.get(self.site + route, timeout=10)
        return json.loads(r.text)

    def GetJSON(self, page):
        """Get the json object of an API page"""
        r = self.session.get(self.site + page, timeout=10)
        return json.loads(r.text)

