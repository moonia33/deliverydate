# deliverydate – Pristatymo datos modulis (PrestaShop 8/9)

[![Atsisiųsti modulį](https://img.shields.io/badge/Atsisi%C5%B3sti-deliverydate.zip-2ea44f?style=for-the-badge)](https://github.com/moonia33/deliverydate/releases/latest/download/deliverydate.zip)

Modulis leidžia greitai valdyti vieną pristatymo datą visai parduotuvei:

- rankiniu būdu per modulio nustatymus;
- arba automatiškai per `cron` (URL).

Atnaujinta data įrašoma į:

- nustatymą „Prekių, esančių sandėlyje, pristatymo laikas“ (konfigūracijos raktas `PS_LABEL_DELIVERY_TIME_AVAILABLE`)
- kurjerių `delay` (DB: `carrier_lang`)

Prekės puslapyje data rodoma per PrestaShop temų naudojamą `$product.delivery_information`.

## Suderinamumas

- PrestaShop v8.xx
- PrestaShop v9.xx

## Diegimas

1. Atsisiųskite `deliverydate.zip` (mygtukas viršuje).
2. PrestaShop administracijoje: **Modules → Module Manager → Upload a module**.
3. Įkelkite ZIP ir įdiekite.

## Nustatymai

Modulio nustatymuose pasirinkite vieną režimą:

1. **Dienų skaičius** – kiek dienų pridėti prie šiandienos datos
2. **Konkreti data** – fiksuota data `YYYY-MM-DD`
3. **Laisvas tekstas** – įrašomas tekstas (pvz. „Pristatymas rytoj“)

Išsaugojus nustatymus, modulis iškart atnaujina pristatymo laiką ir kurjerius.
Taip pat yra mygtukas **Atnaujinti dabar** (nekeičiant nustatymų).

## Cron

Modulio nustatymuose matysite `Cron URL` su token’u.
Sukonfigūruokite cron (pvz. kartą per dieną), kuris užkrauna tą URL.

Pvz. serverio cron’e (kasdien 03:00):

```bash
0 3 * * * curl -fsS "https://JUSU-DOMENAS/module/deliverydate/cron?token=..." > /dev/null
```

## Pastabos

- Modulis nekeičia PrestaShop core failų ir nenaudoja `override`.
- Išjungus modulį, cron atnaujinimas sustoja.
- Įrašyti duomenys (konfigūracijos reikšmė ir kurjerių `delay`) yra persistuojami DB.

## Autorius

moonia
