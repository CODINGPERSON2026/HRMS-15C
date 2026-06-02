## Since the Schedules table is empty we need to insert the below data into it


INSERT INTO hrms.honours_board (
    id,
    army_number,
    medal_icon,
    comments,
    commendation,
    appreciation_type,
    appreciation_date,
    image
)
VALUES
(
    1,
    '15724706M',
    'fa-solid fa-flag',
    '',
    'This officer conceptualized and implemented a digital attendance tracking system for the battalion that reduced administrative overhead by 40%. His initiative brought measurable efficiency to routine operations.',
    'Excellence',
    '2026-05-20',
    '/static/honours/15724706M.png'
),
(
    2,
    '15744564F',
    'fa-solid fa-gem',
    '',
    'amazing work done , new innovation and execution of ideas',
    'Innovation',
    '2026-05-15',
    '/static/honours/15744564F.png'
),
(
    3,
    'A4204797K',
    'fa-solid fa-crown',
    '',
    'This is to formally commend [Name] for their outstanding dedication, professionalism, and consistent contributions to the team. Their positive attitude, strong work ethic, and ability to deliver high-quality results have made a significant impact on the organization.',
    'Innovation',
    '2026-05-20',
    '/static/honours/A4204797K.png'
);