<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"/>
    <title>ﻋﻘﺪ وﺳﺎطﺔ ﻋﻘﺎرﯾﺔ (وﺳﯿﻂ ﻋﻘﺎري ﻣﺴﺘﻘﻞ)</title>
    <style>
        /* 1) Define your page size, margins, and hook up header/footer */
        @page {
            margin: 150px 45px 150px 45px;
        }

        @page {
            /* MUST match the htmlpageheader name="MyHeader" below */
            header: html_MyHeader;
        }

        @page {
            /* MUST match the htmlpageheader name="MyHeader" below */
            footer: html_MyFooter;
        }

        /* 2) Base body styles */
        body {
            font-family: 'amiri', sans-serif;
            font-size: 14px;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 0;
        }

        /* 3) Optional: page-break helper */
        .page-break {
            page-break-before: always;
        }

        /* 6) RTL helper */
        .rtl-text {
            direction: rtl;
        }

        table.info-table, table.info-table td, table.info-table th {
            text-align: right;
            padding-bottom: 1em;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table {
            width: 100%;
            margin-bottom: 0.5em;
            margin-top: 1.5em;
        }

        .left-th {
            text-align: left;
            padding-left: 5px;
        }

        .right-th {
            text-align: right;
        }

        .justified {
            /* fully justify the lines */
            text-align: justify;
            text-justify: inter-word;

            /* allow words to break and hyphenate */
            -webkit-hyphens: auto;
            -moz-hyphens: auto;
            -ms-hyphens: auto;
            hyphens: auto;
            overflow-wrap: break-word;
        }

        .spaced-text {
            line-height: 1.5;
        }

        .justified[lang="en"] {
            /* To be added in Production */
        }

        .centred-text {
            text-align: center;
        }

        .content-txt {
            font-size: 14px;
        }
    </style>
</head>
<body>

<!-- 7) Your named header block (no html_ in the name) -->
<htmlpageheader name="MyHeader">
    <div style="margin-left: -45px; margin-right: -45px; height: 300px;">
        <img
            src="{{ public_path('dist/img/agreement_header.jpg') }}"
            alt="Company Header"
            style="width:100%; max-width:900mm;"
        />
    </div>
</htmlpageheader>

<!-- 8) Your named footer block (no html_ in the name) -->
<htmlpagefooter name="MyFooter">
    <div style="height: 50px; text-align: center;">
        <table style="border-collapse:collapse; border:none; width: 100%">
            <tr>
                <td style="width: 48%; text-align: center; font-weight: bold;">ﺗﻮﻗﯿﻊ اﻟﻄﺮف اﻷول:</td>
                <td>{PAGENO}</td>
                <td style="width: 48%; text-align: center; font-weight: bold;">ﺗﻮﻗﯿﻊ اﻟﻄﺮف الثاني:</td>
            </tr>
        </table>
    </div>

    <div style="position: center; margin-left: -45px; margin-right: -45px;
      bottom: 0;       /* stick to the bottom of the page */
      right:  0;       /* ignore the right margin entirely */
      ">
        <img
            src="{{ public_path('dist/img/agreement_footer.jpg') }}"
            alt="Company Footer"
            style="width:100%; max-width:900mm; height:auto;"
        />
    </div>
</htmlpagefooter>

<!-- 9) Your main content -->
<main>
    <h1 class="rtl-text" style="text-align:center;">
        ﻋﻘﺪ وﺳﺎطﺔ ﻋﻘﺎرﯾﺔ (وﺳﯿﻂ ﻋﻘﺎري ﻣﺴﺘﻘﻞ)
    </h1>

    <p class="content-txt">ﺗﻢ إﺑﺮام ھﺬا اﻟﻌﻘﺪ ﺑﺘﺎرﯾﺦ: {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
    <p class="content-txt">ﺑﯿﻦ ﻛﻞ ﻣﻦ: </p>

    <h3>الطرف الأول:</h3>
    <table class="info-table">
        <tr>
            <th>ﺷﺮﻛﺔ روح اﻟﻌﻄﺎء ﻟﻠﺘﻄﻮﯾﺮ اﻟﻌﻘﺎري</th>
        </tr>
        <tr>
            <th>
                وﯾُﺸﺎر إﻟﯿﮭﺎ ﻻﺣﻘﺎً ﺑـ " اﻟﻤﻄﻮّر"
            </th>
        </tr>
    </table>

    <br/>

    <h3>الطرف الثاني:</h3>
    <table class="info-table">
        <tr>
            <th style="width: 12%;">اﻻﺳﻢ اﻟﻜﺎﻣﻞ:</th>
            <td class="content-txt">{{ $user->full_name }} </td>
        </tr>
        <tr>
            <th>
                 رقم {{ $user->id_type === 'ID' ? 'الهوية' : 'جواز السفر' }}:
            </th>
            <td class="content-txt">{{ $user->id_number }} </td>
        </tr>
        <tr>
            <th>الجنسية:</th>
            <td class="content-txt">{{ DB::table('countries')->where('id', $user->nationality)->value('name') }}</td>
        </tr>
        <tr>
            <th>العنوان:</th>
            <td class="content-txt">{{ $user->address }} </td>
        </tr>
        <tr>
            <th colspan="2">
                وﯾُﺸﺎر إﻟﯿﮫ ﻻﺣﻘﺎً ﺑـ "اﻟﻮﺳﯿﻂ اﻟﻌﻘﺎري اﻟﻤﺴﺘﻘﻞ"
            </th>
        </tr>
    </table>

    <div class="page-break"></div>

    <h4>اﻟﻤﺎدة (1): ﻣﻮﺿﻮع اﻟﻌﻘﺪ</h4>
    <p>
        ﯾﻌﯿّﻦ اﻟﻤﻄﻮّر اﻟﻮﺳﯿﻂ اﻟﻌﻘﺎري اﻟﻤﺴﺘﻘﻞ ﻛﻮﺳﯿﻂ ﻏﯿﺮ ﺣﺼﺮي ﻟﺘﺴﻮﯾﻖ وﺑﯿﻊ وﺣﺪات ﻣﺸﺮوع City Giving، وذﻟﻚ وﻓﻘﺎً ﻟﺸﺮوط وأﺣﻜﺎم
        ھﺬا اﻟﻌﻘﺪ.
    </p>

    <br/>

    <h4>اﻟﻤﺎدة (2): طﺒﯿﻌﺔ اﻟﻌﻼﻗﺔ</h4>
    <ol>
        <li>ھﺬا اﻟﻌﻘﺪ ﻏﯿﺮ ﺣﺼﺮي، وﯾﺤﻖ ﻟﻠﻤﻄﻮّر اﻟﺘﻌﺎﻗﺪ ﻣﻊ وﺳﻄﺎء آﺧﺮﯾﻦ ﻓﻲ أي وﻗﺖ.</li>
        <li>ﯾُﻌﺪ اﻟﻮﺳﯿﻂ اﻟﻌﻘﺎري اﻟﻤﺴﺘﻘﻞ ﻣﺘﻌﺎﻗﺪاً ﻣﺴﺘﻘﻼً وﻻ ﺗﺮﺑﻄﮫ ﺑﺎﻟﻤﻄﻮّر أي ﻋﻼﻗﺔ ﻋﻤﻞ أو ﺗﻮظﯿﻒ أو ﺷﺮاﻛﺔ.</li>
        <li>ﻻ ﯾُﻌﺪ ھﺬا اﻟﻌﻘﺪ وﻛﺎﻟﺔ ﻗﺎﻧﻮﻧﯿﺔ أو ﺗﻔﻮﯾﻀﺎً، وﻻ ﯾﺤﻖ ﻟﻠﻮﺳﯿﻂ اﻟﻌﻘﺎري اﻟﻤﺴﺘﻘﻞ ﺗﻤﺜﯿﻞ اﻟﻤﻄﻮّر أو اﻟﺘﻮﻗﯿﻊ أو اﻻﻟﺘﺰام
            ﻧﯿﺎﺑﺔً ﻋﻨﮫ ﺑﺄي ﺷﻜﻞ ﻣﻦ اﻷﺷﻜﺎل.
        </li>
    </ol>

    <h4>اﻟﻤﺎدة (3): اﻟﺘﺰاﻣﺎت اﻟﻮﺳﯿﻂ اﻟﻌﻘﺎري اﻟﻤﺴﺘﻘﻞ</h4>
    <h5>ﯾﻠﺘﺰم اﻟﻮﺳﯿﻂ اﻟﻌﻘﺎري اﻟﻤﺴﺘﻘﻞ ﺑﻤﺎ ﯾﻠﻲ:</h5>
    <ol>
        <li>ﺗﺴﻮﯾﻖ اﻟﻤﺸﺮوع وﻓﻖ اﻟﻤﻌﻠﻮﻣﺎت واﻟﻤﺤﺘﻮى واﻷﺳﻌﺎر وﺧﻄﻂ اﻟﺪﻓﻊ اﻟﻤﻌﺘﻤﺪة ﺧﻄﯿﺎً ﻣﻦ اﻟﻤﻄﻮّر ﻓﻘﻂ.</li>
        <li>اﻻﻣﺘﻨﺎع ﻋﻦ ﺗﻘﺪﯾﻢ أي وﻋﻮد أو اﻟﺘﺰاﻣﺎت أو ﺿﻤﺎﻧﺎت ﺻﺮﯾﺤﺔ أو ﺿﻤﻨﯿﺔ ﻏﯿﺮ ﻣﻌﺘﻤﺪة ﺑﺎﺳﻢ اﻟﻤﻄﻮّر أو اﻟﻤﺸﺮوع.</li>
        <li>اﻻﻟﺘﺰام ﺑﺎﻟﻘﻮاﻧﯿﻦ واﻷﻧﻈﻤﺔ واﻟﺘﻌﻠﯿﻤﺎت اﻟﻤﻌﻤﻮل ﺑﮭﺎ ﻟﺪى اﻟﺠﮭﺎت اﻟﻤﺨﺘﺼﺔ.</li>
        <li>اﻟﺤﻔﺎظ ﻋﻠﻰ ﺳﻤﻌﺔ اﻟﻤﺸﺮوع واﻟﻤﻄﻮّر وﻋﺪم اﻹﺳﺎءة ﻟﮭﻤﺎ ﺑﺄي ﺷﻜﻞ.</li>
        <li>ﻋﺪم ﺗﻌﺪﯾﻞ أو إﻋﺎدة ﺻﯿﺎﻏﺔ أي ﻣﺤﺘﻮى ﺗﺴﻮﯾﻘﻲ أو إﻋﻼﻧﻲ دون ﻣﻮاﻓﻘﺔ ﺧﻄﯿﺔ ﻣﺴﺒﻘﺔ ﻣﻦ اﻟﻤﻄﻮّر.</li>
    </ol>

    <br/>

    <h4>اﻟﻤﺎدة (4): اﻟﻌﻤﻮﻟﺔ</h4>
    <ol>
        <li>
            ﯾﺴﺘﺤﻖ اﻟﻮﺳﯿﻂ اﻟﻌﻘﺎري اﻟﻤﺴﺘﻘﻞ ﻋﻤﻮﻟﺔ ﺗُﺤﺘﺴﺐ ﺷﮭﺮﯾﺎً وﻓﻖ ﻋﺪد ﻣﺒﯿﻌﺎت اﻟﻮﺣﺪات اﻟﻤﺤﻘﻘﺔ ﺧﻼل ﻧﻔﺲ اﻟﺸﮭﺮ، وذﻟﻚ ﻛﻤﺎ ﯾﻠﻲ:
            <ul>
                <li>(%2) ﻋﻦ ﻛﻞ واﺣﺪة ﻣﻦ أول ﺛﻼث (3) ﻣﺒﯿﻌﺎت وﺣﺪات ﻣﺤﻘﻘﺔ ﺧﻼل اﻟﺸﮭﺮ.</li>
                <li>(%3) ﻋﻦ ﻛﻞ ﻣﺒﯿﻌﺎت وﺣﺪات ﺗﺒﺪأ ﻣﻦ اﻟﻤﺒﯿﻌﺎت اﻟﺮاﺑﻌﺔ ﺧﻼل اﻟﺸﮭﺮ ﻓﻤﺎ ﻓﻮق.</li>
            </ul>
        </li>
        <li>ﺗُﺤﺘﺴﺐ ﻣﺒﯿﻌﺎت اﻟﻮﺣﺪات ﻷﻏﺮاض ھﺬا اﻟﻌﻘﺪ ﻋﻨﺪ إﺗﻤﺎم اﻟﺒﯿﻊ وﺗﻮﻗﯿﻊ ﻋﻘﺪ اﻟﺒﯿﻊ اﻟﻨﮭﺎﺋﻲ ﺑﯿﻦ اﻟﻤﻄﻮّر واﻟﻤﺸﺘﺮي، واﻋﺘﻤﺎد اﻟﺼﻔﻘﺔ ﻣﻦ اﻟﻤﻄﻮّر.</li>
        <li>ﺗُﺪﻓﻊ اﻟﻌﻤﻮﻟﺔ ﺑﻌﺪ ﺳﺪاد اﻟﻤﺸﺘﺮي اﻟﺪﻓﻌﺔ اﻟﻤﺘﻔﻖ ﻋﻠﯿﮭﺎ ﺣﺴﺐ ﺳﯿﺎﺳﺔ اﻟﻤﻄﻮّر.</li>
        <li>ﻻ ﺗُﺴﺘﺤﻖ أي ﻋﻤﻮﻟﺔ ﻓﻲ ﺣﺎل إﻟﻐﺎء اﻟﺼﻔﻘﺔ ﻷي ﺳﺒﺐ ﺧﺎرج ﻋﻦ إرادة اﻟﻤﻄﻮّر.</li>
    </ol>

    <br/>

    <h4>اﻟﻤﺎدة (5): اﻹﻋﻼﻧﺎت واﻟﺘﺴﻮﯾﻖ</h4>
    <ol>
        <li>ﻻ ﯾﺤﻖ ﻟﻠﻮﺳﯿﻂ اﻟﻌﻘﺎري اﻟﻤﺴﺘﻘﻞ اﻹﻋﻼن أو اﻟﺘﺮوﯾﺞ ﻟﻠﻤﺸﺮوع إﻻ ﺑﻌﺪ اﻟﺤﺼﻮل ﻋﻠﻰ ﺷﮭﺎدة ﻋﺪم ﻣﻤﺎﻧﻌﺔ (NOC) ﺻﺎدرة ﻋﻦ اﻟﻤﻄﻮّر.</li>
        <li>ﯾﻠﺘﺰم اﻟﻮﺳﯿﻂ اﻟﻌﻘﺎري اﻟﻤﺴﺘﻘﻞ ﺑﺠﻤﯿﻊ اﻟﺸﺮوط اﻟﻮاردة ﻓﻲ ﺷﮭﺎدة ﻋﺪم اﻟﻤﻤﺎﻧﻌﺔ.</li>
        <li>أي إﺧﻼل ﺑﺸﺮوط اﻹﻋﻼن ﺳﺒﺒﺎً ﻣﺒﺎﺷﺮاً ﻹﻧﮭﺎء ھﺬا اﻟﻌﻘﺪ دون أي ﺗﻌﻮﯾﺾ.</li>
    </ol>

    <br/>

    <h4>اﻟﻤﺎدة (6): اﻟﺴﺮﯾﺔ</h4>
    <ol>
        <li>
            ﯾﻠﺘﺰم اﻟﻮﺳﯿﻂ اﻟﻌﻘﺎري اﻟﻤﺴﺘﻘﻞ ﺑﺎﻟﺤﻔﺎظ ﻋﻠﻰ ﺳﺮﯾﺔ ﺟﻤﯿﻊ اﻟﻤﻌﻠﻮﻣﺎت واﻟﺒﯿﺎﻧﺎت اﻟﻤﺘﻌﻠﻘﺔ ﺑﺎﻟﻤﺸﺮوع واﻟﻤﻄﻮّر واﻟﻌﻤﻼء.
        </li>
        <li>ﯾﺴﺘﻤﺮ اﻻﻟﺘﺰام ﺑﺎﻟﺴﺮﯾﺔ ﺣﺘﻰ ﺑﻌﺪ اﻧﺘﮭﺎء أو ﻓﺴﺦ ھﺬا اﻟﻌﻘﺪ ﻷي ﺳﺒﺐ.</li>
    </ol>

    <br/>

    <h4>اﻟﻤﺎدة (7): ﻣﺪة اﻟﻌﻘﺪ</h4>
    <ol>
        <li>
            ﻣﺪة ھﺬا اﻟﻌﻘﺪ ﺳﻨﺔ واﺣﺪة اﻋﺘﺒﺎراً ﻣﻦ ﺗﺎرﯾﺦ ﺗﻮﻗﯿﻌﮫ.
        </li>
        <li>ﯾﺘﺠﺪد اﻟﻌﻘﺪ ﺗﻠﻘﺎﺋﯿﺎً ﻟﻤﺪة ﻣﻤﺎﺛﻠﺔ ﻣﺎ ﻟﻢ ﯾُﺨﻄﺮ أﺣﺪ اﻟﻄﺮﻓﯿﻦ اﻵﺧﺮ ﺧﻄﯿﺎً ﺑﻌﺪم اﻟﺮﻏﺒﺔ ﻓﻲ اﻟﺘﺠﺪﯾﺪ.</li>
    </ol>

    <br/>

    <h4>اﻟﻤﺎدة (8): إﻧﮭﺎء اﻟﻌﻘﺪ</h4>
    <ol>
        <li>ﯾﺤﻖ ﻟﻠﻤﻄﻮّر إﻧﮭﺎء ھﺬا اﻟﻌﻘﺪ ﻓﻲ أي وﻗﺖ ودون إﺑﺪاء اﻷﺳﺒﺎب، وذﻟﻚ ﺑﺈﺷﻌﺎر ﺧﻄﻲ أو ﻋﺒﺮ اﻟﺒﺮﯾﺪ اﻹﻟﻜﺘﺮوﻧﻲ اﻟﺮﺳﻤﻲ.</li>
        <li>ﻓﻲ ﺣﺎل ﻣﺨﺎﻟﻔﺔ اﻟﻮﺳﯿﻂ اﻟﻌﻘﺎري اﻟﻤﺴﺘﻘﻞ ﻷي ﺑﻨﺪ ﻣﻦ ﺑﻨﻮد ھﺬا اﻟﻌﻘﺪ أو ﺷﺮوط ﺷﮭﺎدة ﻋﺪم اﻟﻤﻤﺎﻧﻌﺔ، ﯾُﻌﺘﺒﺮ اﻟﻌﻘﺪ ﻣﻔﺴﻮﺧﺎً ﺣﻜﻤﺎً ﻣﻦ ﺗﺎرﯾﺦ إﺷﻌﺎر اﻹﻟﻐﺎء دون اﻟﺤﺎﺟﺔ ﻷي إﻧﺬار ﻣﺴﺒﻖ ودون أي ﺗﻌﻮﯾﺾ.</li>
    </ol>

    <br/>

    <h4>اﻟﻤﺎدة (9): ﻋﺪم اﻟﻤﺴﺆوﻟﯿﺔ</h4>
    <ol>
        <li>
            ﻻ ﯾﺘﺤﻤﻞ اﻟﻤﻄﻮّر أي ﻣﺴﺆوﻟﯿﺔ ﻗﺎﻧﻮﻧﯿﺔ أو ﻣﺎﻟﯿﺔ أو ﺗﻌﺎﻗﺪﯾﺔ ﻧﺎﺗﺠﺔ ﻋﻦ ﺗﺼﺮﻓﺎت اﻟﻮﺳﯿﻂ اﻟﻌﻘﺎري اﻟﻤﺴﺘﻘﻞ ﺗﺠﺎه أي طﺮف ﺛﺎﻟﺚ.
        </li>
        <li>ﯾﺘﺤﻤﻞ اﻟﻮﺳﯿﻂ اﻟﻌﻘﺎري اﻟﻤﺴﺘﻘﻞ ﻛﺎﻣﻞ اﻟﻤﺴﺆوﻟﯿﺔ ﻋﻦ أي اﻟﺘﺰام أو ﻣﺨﺎﻟﻔﺔ ﺗﺼﺪر ﻋﻨﮫ ﺧﺎرج ﻧﻄﺎق ھﺬا اﻟﻌﻘﺪ.</li>
    </ol>

    <br/>

    <h4>المادة (10): اﻟﻘﺎﻧﻮن واﻻﺧﺘﺼﺎص</h4>
    <h5>
        ﯾﺨﻀﻊ ھﺬا اﻟﻌﻘﺪ ﻷﺣﻜﺎم ﻗﻮاﻧﯿﻦ اﻟﻤﻤﻠﻜﺔ اﻷردﻧﯿﺔ اﻟﮭﺎﺷﻤﯿﺔ، وﯾﻜﻮن اﻻﺧﺘﺼﺎص اﻟﻘﻀﺎﺋﻲ ﻟﻤﺤﺎﻛﻤﮭﺎ.
    </h5>

    <br/>

    <h4>اﻟﻤﺎدة (11): أﺣﻜﺎم ﻋﺎﻣﺔ</h4>
    <ol>
        <li>
            ﻻ ﯾﺠﻮز ﺗﻌﺪﯾﻞ أي ﺑﻨﺪ ﻣﻦ ﺑﻨﻮد ھﺬا اﻟﻌﻘﺪ إﻻ ﺑﻤﻮﺟﺐ ﻣﻠﺤﻖ ﺧﻄﻲ ﻣﻮﻗﻊ ﻣﻦ اﻟﻄﺮﻓﯿﻦ.
        </li>
        <li>
            ﯾُﻌﺪ ھﺬا اﻟﻌﻘﺪ ﻛﺎﻣﻼً وﻧﮭﺎﺋﯿﺎً وﯾﻠﻐﻲ أي اﺗﻔﺎﻗﺎت أو ﺗﻔﺎھﻤﺎت ﺳﺎﺑﻘﺔ ﺷﻔﻮﯾﺔ أو ﺧﻄﯿﺔ.
        </li>
    </ol>

    <br/>
    <br/>
    <br/>

    <h3 style="text-align: center;">اﻟﺘﻮﻗﯿﻊ:</h3>
    <table style="width: 100%">
        <tr>
            <td style="width:50%; direction:rtl; text-align:right; vertical-align:top; line-height:1.7; padding-left:12px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                    <tr>
                        <td align="center" style="text-align:center; line-height:1.7;">
                            <div style="font-weight:bold; font-size:16px;">
                                اﻟﻄﺮف اﻷول (اﻟﻤﻄﻮّر):
                            </div>
                            <div style="font-weight:bold; font-size:16px; margin-top:6px;">
                                ﺷﺮﻛﺔ روح اﻟﻌﻄﺎء ﻟﻠﺘﻄﻮﯾﺮ اﻟﻌﻘﺎري
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- right-aligned content -->
                <div style="text-align:right;"><strong>الاسم:</strong> <img src="{{ public_path('dist/img/black_line.svg') }}" width="290" height="2" alt="___"/></div>
                <br/>
                <div style="text-align:right;"><strong>اﻟﻤﻨﺼﺐ:</strong> <img src="{{ public_path('dist/img/black_line.svg') }}" width="277" height="2" alt="___"/></div>
                <br/>
                <div style="text-align:right;"><strong>اﻟﺘﻮﻗﯿﻊ:</strong> <img src="{{ public_path('dist/img/black_line.svg') }}" width="287" height="2" alt="___"/></div>
                <br/>
                <div style="text-align:right;"><strong>اﻟﺨﺘﻢ:</strong></div>

            </td>
            <td style="width:1px; padding:0; background:#000;"></td>
            <td style="width:50%; direction:rtl; text-align:right; vertical-align:top; line-height:1.7; padding-right:12px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                    <tr>
                        <td align="center" style="text-align:center; line-height:1.7;">
                            <div style="font-weight:bold; font-size:16px;">
                                اﻟﻄﺮف اﻟﺜﺎﻧﻲ (اﻟﻮﺳﯿﻂ اﻟﻌﻘﺎري اﻟﻤﺴﺘﻘﻞ):
                            </div>
                            <div style="font-weight:bold; font-size:16px; margin-top:6px;">
                                &nbsp;
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- right-aligned content -->
                <div style="text-align:right;"><strong>الاسم:</strong> {{ $user->full_name }}</div>
                <br/>
                <div style="text-align:right;"><strong>اﻟﺘﻮﻗﯿﻊ:</strong> <img src="{{ public_path('dist/img/black_line.svg') }}" width="280" height="2" alt="___"/></div>
                <br/>
                <div style="text-align:right;"><strong>التاريخ:</strong> <img src="{{ public_path('dist/img/black_line.svg') }}" width="281" height="2" alt="___"/></div>

            </td>
        </tr>
    </table>

    <!--
<div class="page-break"></div>
-->
</main>
</body>
</html>
