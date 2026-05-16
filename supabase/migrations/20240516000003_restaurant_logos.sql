-- Align restaurant logos with assets in images/ (Crispy King = ck.png, Krazy Crunch = logos file).
-- Safe to re-run.

update public.restaurants
set logo = 'ck.png'
where id = 1;

update public.restaurants
set logo = 'shop_69462b57326096.76403290.jpg'
where id = 3;
