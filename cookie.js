/**
 * Подключим библиотеку puppeteer.
 */
const puppeteer = require('puppeteer');
const url = 'https://www.iaai.com/vehicledetails/37926262?RowNumber=2';

async function ssr() {
    const browser = await puppeteer.launch({ headless: true, args: ['--disable-dev-shm-usage', '--no-sandbox'] });
    const page = await browser.newPage();

    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.111 Safari/537.36');
    await page.goto(url, { waitUntil: 'networkidle2' });

// хватило и этой куки одной исчтоник https://stackoverflow.com/questions/49389775/puppeteers-page-cookies-not-retrieving-all-cookies-shown-in-the-chrome-dev-to
//var data = await page._client.send('Network.getAllCookies');
// можно и так больше кукиес
    var cookies = await page.cookies();
//console.log(data, 'data');
    console.log(JSON.stringify(cookies));
    await browser.close();
}

ssr();
