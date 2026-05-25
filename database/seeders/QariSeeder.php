<?php

namespace Database\Seeders;

use App\Models\Qari;
use App\Models\User;
use Illuminate\Database\Seeder;

class QariSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@tilawa.app')->first();

        $qaris = [
            [
                'name_ar'      => 'عبد الباسط عبد الصمد',
                'name_en'      => 'Abdul Basit Abd us-Samad',
                'slug'         => 'abdul-basit-abd-us-samad',
                'image'        => 'qari-images/abdul-basit-abd-us-samad.jpg',
                'biography_ar' => 'الشيخ عبد الباسط عبد الصمد (1927–1988) أحد أعظم قراء القرآن الكريم في التاريخ الحديث، وُلد في قرية أرمنت بمحافظة قنا في صعيد مصر. أتمّ حفظ القرآن وهو في العاشرة من عمره، واشتُهر بصوته الذهبي الاستثنائي وتحكمه الفائق في مقامات التلاوة، مما أكسبه لقب "صوت القرآن". تتميز تلاوته المجوّدة بالتحكم الخارق في النَّفَس، والأداء العاطفي المرتفع الطبقة، والانتشار العالمي الواسع الذي لم يُضاهَ حتى اليوم.',
                'biography_en' => 'Sheikh Abdul Basit Abd us-Samad (1927–1988) is unquestionably one of the most famous Mujawwad reciters in history, born in Armant, Qena Governorate, Upper Egypt. He completed Quran memorization by age ten and became renowned worldwide for his extraordinary breath control, high-pitched emotional delivery, and exceptional golden voice, earning him the title "Voice of the Quran." His Mujawwad recordings remain a priceless legacy inspiring millions of Muslims worldwide.',
                'is_featured'  => true,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'مصطفى إسماعيل',
                'name_en'      => 'Mustafa Ismail',
                'slug'         => 'mustafa-ismail',
                'image'        => 'qari-images/mustafa-ismail.jpg',
                'biography_ar' => 'الشيخ مصطفى إسماعيل (1905–1978) من أبرز قراء القرآن الكريم في مصر، وُلد في قرية ميت غزال بمحافظة الغربية. يُلقَّب بـ"ملك المقامات" لقدرته الاستثنائية الفريدة على نسج المقامات الموسيقية المعقدة كالبياتي والصبا والراست في تلاوته بأسلوب يُبرز معاني الآيات ويُعلي من تأثيرها الروحي. كان حضوره في الحفلات الدينية الكبرى يستقطب الآلاف، وخلّف تراثاً صوتياً يُعدّ تحفة فنية وروحية في مدرسة التلاوة المجوّدة.',
                'biography_en' => 'Sheikh Mustafa Ismail (1905–1978) is often referred to as the "King of Maqamat," born in Meet Ghazal, Gharbia Governorate, Egypt. He possessed an unparalleled ability to weave complex musical scales into his recitation to highlight the meaning of the verses, transitioning effortlessly between Bayati, Saba, Rast, and other maqamat. His presence at major religious gatherings drew thousands of listeners, and his recordings are considered masterpieces of the Mujawwad art.',
                'is_featured'  => true,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'محمد صديق المنشاوي',
                'name_en'      => 'Mohamed Siddiq El-Minshawi',
                'slug'         => 'mohamed-siddiq-el-minshawi',
                'image'        => 'qari-images/mohamed-siddiq-el-minshawi.jpg',
                'biography_ar' => 'الشيخ محمد صديق المنشاوي (1920–1969) من أعظم قراء القرآن في التاريخ، وُلد في مدينة المنشاة بمحافظة سوهاج في صعيد مصر. حفظ القرآن في سن السادسة تقريباً وتتلمذ على يد والده الشيخ صديق المنشاوي. اشتُهر بصوته الروحاني العذب المبكي وأسلوبه المجوّد العميق التأثير في النفوس، حتى باتت تلاوته مقترنة بالدموع والخشوع. رحل عن عالمنا وهو في التاسعة والأربعين، تاركاً إرثاً لا يفنى.',
                'biography_en' => 'Sheikh Mohamed Siddiq El-Minshawi (1920–1969) is among the greatest Quran reciters in history, born in Al-Manshah, Sohag Governorate, Upper Egypt. Famous for his deeply spiritual, resonant, and sorrowful voice, his Mujawwad style is beloved worldwide for its profound emotional impact and the weeping it evokes in listeners. He passed away at forty-nine, leaving behind recordings still considered among the finest in Quranic recitation history.',
                'is_featured'  => true,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'محمود خليل الحصري',
                'name_en'      => 'Mahmoud Khalil Al-Hussary',
                'slug'         => 'mahmoud-khalil-al-hussary',
                'image'        => 'qari-images/mahmoud-khalil-al-hussary.jpg',
                'biography_ar' => 'الشيخ محمود خليل الحصري (1917–1980) عَلَمٌ بارز في علوم التجويد والقراءات، وُلد في قرية شبراخيت بمحافظة البحيرة في مصر. عُيّن شيخاً للمقرئين في مصر والصوتَ الرسمي للإذاعة المصرية. وإن كانت تلاوته المرتّلة المعيار العالمي في التعليم، فإن تسجيلاته المجوّدة تُعدّ دروساً متكاملة في الدقة التجويدية المطلقة مقرونةً بالإيقاع المهيب، وقد نالت جوائز دولية رفيعة.',
                'biography_en' => 'Sheikh Mahmoud Khalil Al-Hussary (1917–1980) was a towering figure in Tajweed sciences, born in Shubrakheit, Beheira Governorate, Egypt, and appointed Sheikh of Reciters in Egypt. While his Murattal is the global standard for learning, his Mujawwad recordings are considered masterclasses in absolute, uncompromising precision of Tajweed rules combined with a majestic pace. His work has won international awards and remains an indispensable educational reference worldwide.',
                'is_featured'  => true,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'كامل يوسف البهتيمي',
                'name_en'      => 'Kamel Youssef Al-Bahtimi',
                'slug'         => 'kamel-youssef-al-bahtimi',
                'image'        => 'qari-images/kamel-youssef-al-bahtimi.jpg',
                'biography_ar' => 'الشيخ كامل يوسف البهتيمي قارئ مصري بارز اشتُهر بصوته القوي الجهوري وانتقالاته السلسة الاستثنائية بين المقامات الموسيقية المختلفة خلال حفلات التلاوة الجماهيرية الكبرى. كانت حضوره يسحر الجمهور ويُذهل الخاصة، وتُعدّ تلاواته المجوّدة في التجمعات العامة الكبرى نموذجاً لما يجمع بين الإبداع الصوتي والأثر الروحي العميق.',
                'biography_en' => 'Sheikh Kamel Youssef Al-Bahtimi is a prominent Egyptian reciter known for his powerful, booming voice and incredibly smooth transitions between different melodic modes during live public gatherings. His commanding presence and mastery of Mujawwad performance captivated audiences and made him a celebrated figure in the golden age of Egyptian Quranic recitation.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'محمود علي البنا',
                'name_en'      => 'Mahmoud Ali Al-Banna',
                'slug'         => 'mahmoud-ali-al-banna',
                'image'        => 'qari-images/mahmoud-ali-al-banna.jpg',
                'biography_ar' => 'الشيخ محمود علي البنا شخصية محورية في الإذاعة المصرية وركيزة أساسية في مدرسة التلاوة المجوّدة. يتميز أسلوبه بالجلال والرصانة والعمق الصوتي الآسر الذي يشدّ الأسماع ويملأ القلوب هيبةً وخشوعاً. أسهم إسهاماً بالغاً في ترسيخ معايير التلاوة الفنية على مستوى الإذاعة والتسجيل في مصر، وظلت تلاواته مرجعاً راسخاً للأجيال المتعاقبة.',
                'biography_en' => 'Sheikh Mahmoud Ali Al-Banna was a central figure in Egyptian radio and a cornerstone of the Mujawwad school. His style is characterized by a dignified, robust, and deeply resonant delivery that commands attention. He made significant contributions to establishing artistic recitation standards in Egyptian broadcasting and recording, and his recitations remain a lasting reference for subsequent generations.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'أبو العينين شعيشع',
                'name_en'      => 'Abolainain Shuaisha',
                'slug'         => 'abolainain-shuaisha',
                'image'        => 'qari-images/abolainain-shuaisha.jpg',
                'biography_ar' => 'الشيخ أبو العينين شعيشع قارئ مصري مُتميز احتفى به الجمهور لامتلاكه نبرة صوتية مميزة بعلوها وقوتها الاستثنائية. عُرف بقدرته على إثارة المشاعر العميقة وتحريك القلوب، وكان ركيزةً لا غنى عنها في حفلات التلاوة المجوّدة الكبرى. صوته الجهوري المميز وأسلوبه المؤثر جعلاه علماً من أعلام الإرث القرآني المصري.',
                'biography_en' => 'Sheikh Abolainain Shuaisha is a celebrated Egyptian reciter renowned for his very distinct, powerful, and immensely commanding vocal tone. His ability to evoke deep emotion made him a staple in grand Mujawwad events. His exceptional voice and moving style established him as a prominent figure in the Egyptian Quranic heritage.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'محمد محمود الطبلاوي',
                'name_en'      => 'Muhammad Mahmoud Tablawi',
                'slug'         => 'muhammad-mahmoud-tablawi',
                'image'        => 'qari-images/muhammad-mahmoud-tablawi.jpeg',
                'biography_ar' => 'الشيخ محمد محمود الطبلاوي قارئ مصري بارز اشتُهر بصوته القوي المميز وطابعه المصري الأصيل. صِيغت تلاواته في معظمها لجمهور الحفلات الحية التي كانت تلتئم خصيصاً للاستماع إلى الأسلوب المجوّد الفني. قدّم الشيخ الطبلاوي مساهمة بارزة في إبقاء جذوة مدرسة التلاوة الكلاسيكية متقدة في الوجدان الديني المصري.',
                'biography_en' => 'Sheikh Muhammad Mahmoud Tablawi is a highly popular Egyptian reciter known for his unique, strong voice and traditional Egyptian flavor. His recitations were heavily tailored for live audiences who gathered specifically to hear the artistic Mujawwad style. He made a significant contribution to keeping the flame of the classical recitation school alive in Egyptian religious consciousness.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'راغب مصطفى غلوش',
                'name_en'      => 'Raghib Mustafa Ghalwash',
                'slug'         => 'raghib-mustafa-ghalwash',
                'image'        => 'qari-images/raghib-mustafa-ghalwash.jpg',
                'biography_ar' => 'الشيخ راغب مصطفى غلوش قارئ مصري متميز عُرف بحضوره القوي المهيب وأوتاره الصوتية الجبارة وتحكمه الاستثنائي في عبارات لحنية مديدة بالغة التعقيد في نفَس واحد. أسلوبه المجوّد يجمع الجمال والقوة والدقة، مما يجعله نموذجاً فريداً يُحتذى به في مدرسة التلاوة المصرية.',
                'biography_en' => 'Sheikh Raghib Mustafa Ghalwash is a distinguished Egyptian reciter known for his commanding presence, strong vocal chords, and mastery of exceptionally long, complex melodic phrasing in a single breath. His Mujawwad style combines beauty, power, and precision, making him a distinctive model in the Egyptian school of Quranic recitation.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'شحات محمد أنور',
                'name_en'      => 'Shahat Muhammad Anwar',
                'slug'         => 'shahat-muhammad-anwar',
                'image'        => 'qari-images/shahat-muhammad-anwar.jpg',
                'biography_ar' => 'الشيخ شحات محمد أنور أحد أبرز أساتذة الأسلوب الكلاسيكي في جيله الأحدث، يُحتفى به لتوظيفه الرفيع للألحان والزخارف الصوتية الرائعة في حفلات التلاوة الجماهيرية الآسرة. يمثل حلقة وصل حية بين الجيل الذهبي من القراء والأجيال المعاصرة، محافظاً على روح المجوّد الكلاسيكي وأسلوبه الفني الأصيل.',
                'biography_en' => 'Sheikh Shahat Muhammad Anwar is a slightly more modern master of the classical Mujawwad style, celebrated for his intricate use of melodies, beautiful vocal ornamentations, and captivating public performances. He represents a living bridge between the golden generation of reciters and contemporary audiences, preserving the spirit and artistry of classical Mujawwad.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'محمد رفعت',
                'name_en'      => 'Muhammad Rifat',
                'slug'         => 'muhammad-rifat',
                'image'        => 'qari-images/muhammad-rifat.jpg',
                'biography_ar' => 'الشيخ محمد رفعت (1882–1950) يُعدّ على نطاق واسع "صوت الجنة" وواحداً من أعظم القراء في تاريخ التلاوة القرآنية. كان أول من تلا القرآن الكريم على الإذاعة المصرية عام 1934، مُرسياً بذلك معياراً ذهبياً لا يُقاس. يتميز أسلوبه بالإحساس الروحي العميق الذي يفوق الوصف، والأداء الصادق الذي يهزّ مشاعر المستمع هزاً عميقاً. وقد غدت تسجيلاته النادرة كنزاً تراثياً لا يُثمَّن.',
                'biography_en' => 'Sheikh Muhammad Rifat (1882–1950) is widely known as the "Voice of Heaven" and one of the greatest Quran reciters in history. He was the very first Qari to recite on Egyptian Radio in 1934, setting the gold standard for all who followed. His style is incredibly deeply felt and spiritual, with an emotional sincerity that moves listeners profoundly. His rare recordings are considered an invaluable cultural treasure.',
                'is_featured'  => true,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'عبد الفتاح الشعشاعي',
                'name_en'      => 'Abdul Fattah Al-Shaashai',
                'slug'         => 'abdul-fattah-al-shaashai',
                'image'        => 'qari-images/abdul-fattah-al-shaashai.jpg',
                'biography_ar' => 'الشيخ عبد الفتاح الشعشاعي (1890–1962) عملاق من عمالقة الجيل الكلاسيكي، يُحتفى به لفهمه العميق لعلم التفسير الذي كان يُجلّيه من خلال انتقالات صوتية درامية ومهيبة. يمثل نموذجاً رفيعاً للقارئ العالِم الذي يُؤوّل الآيات صوتياً بعمق وتدبّر، وقد أثّرت تلاواته تأثيراً بالغاً في الجيل الذهبي اللاحق من القراء.',
                'biography_en' => 'Sheikh Abdul Fattah Al-Shaashai (1890–1962) was a giant of the old generation, highly celebrated for his profound understanding of Tafsir (Quranic exegesis), reflected through dramatic, booming vocal transitions. He represents a high model of the scholar-reciter who interprets verses vocally with depth and contemplation, and his recitations profoundly influenced the subsequent golden generation.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'علي محمود',
                'name_en'      => 'Ali Mahmoud',
                'slug'         => 'ali-mahmoud',
                'image'        => 'qari-images/ali-mahmoud.jpg',
                'biography_ar' => 'الشيخ علي محمود (1878–1946) رائد أسطوري وأستاذ فذّ في تلاوة القرآن الكريم والمقامات العربية الكلاسيكية على حدٍّ سواء. يُنسب إليه الفضل في تعليمه وتأثيره الكبير في جيل كامل من قراء العصر الذهبي، مما يجعله حلقة محورية في سلسلة التراث القرآني المصري. أرسى قواعد فنية وتعليمية لا تزال تُلقي بظلالها على مدرسة التلاوة المصرية حتى اليوم.',
                'biography_en' => 'Sheikh Ali Mahmoud (1878–1946) was a legendary pioneer and a true master of both Quran recitation and classical Arabic Maqamat. He famously taught and influenced an entire generation of golden-age Qaris, making him a pivotal link in the chain of Egyptian Quranic heritage. He established artistic and educational foundations that continue to shape the Egyptian school of recitation to this day.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'أحمد ندا',
                'name_en'      => 'Ahmed Nada',
                'slug'         => 'ahmed-nada',
                'image'        => null,
                'biography_ar' => 'الشيخ أحمد ندا (1852–1932) من أوائل الرواد الذين رسّخوا أسلوب التلاوة المجوّدة بوصفه فناً رفيعاً. أحدث ثورة في مهنة الشيخ القارئ في مصر قبل ظهور الراديو بزمن طويل، وأسّس تقاليد وأعرافاً في الأداء الصوتي القرآني لا تزال صداها تتردد في مدرسة التلاوة المصرية. يُعدّ من الجذور الراسخة التي قامت عليها المدرسة المصرية في فن التلاوة.',
                'biography_en' => 'Sheikh Ahmed Nada (1852–1932) was one of the earliest pioneers of the Mujawwad style as an elite art form. He revolutionized the profession of the Qari in Egypt well before the advent of the radio, establishing performance traditions and conventions that continued to echo through the Egyptian school of recitation. He is considered one of the deep roots upon which the Egyptian recitation tradition was built.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'محمد سلامة',
                'name_en'      => 'Muhammad Salamah',
                'slug'         => 'muhammad-salamah',
                'image'        => 'qari-images/muhammad-salamah.jpg',
                'biography_ar' => 'الشيخ محمد سلامة (1899–1982) تقليدي متشدد لا يُجارى في حرصه على الأصالة الفنية. رفض التلاوة على الراديو سنواتٍ طويلة، فكان الناس يتوافدون إليه بأعداد تعدّ بالآلاف — ما يُضاهي جمهور الملاعب — حضوراً للاستماع إلى أداءه المجوّد الحي. جسّد بتجربته الاستثنائية هذه قوة الأداء القرآني الحيّ وأثره الساحر الذي لا يُضاهيه التسجيل.',
                'biography_en' => 'Sheikh Muhammad Salamah (1899–1982) was a fiercely independent traditionalist who famously refused to recite on the radio for years, drawing immense, stadium-sized crowds who gathered exclusively to hear his live Mujawwad. His extraordinary experience embodied the irreplaceable power and captivating effect of live Quranic performance that no recording can fully replicate.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'طه الفشني',
                'name_en'      => 'Taha Al-Fashni',
                'slug'         => 'taha-al-fashni',
                'image'        => 'qari-images/taha-al-fashni.jpg',
                'biography_ar' => 'الشيخ طه الفشني (1900–1971) قارئ مصري مُحبَّب اشتُهر بتشديه الديني الأخّاذ، وكانت تلاوته القرآنية المجوّدة بالغة الرقي، تتجلى فيها نبرة صوتية عذبة عالية الإيقاع وروح دينية عميقة التأثير. يمثل نموذجاً للقارئ الفنان الذي يُوازن ببراعة بين الجمال الصوتي والأداء الروحاني الصادق.',
                'biography_en' => 'Sheikh Taha Al-Fashni (1900–1971) was a widely beloved Egyptian reciter known for his religious chanting and deeply refined Mujawwad Quran recitation, utilizing a sweet, highly melodious, and deeply spiritual vocal tone. He represents the model of the artist-reciter who skillfully balances vocal beauty with genuine spiritual expression.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'عبد العظيم زاهر',
                'name_en'      => 'Abdul Azim Zaher',
                'slug'         => 'abdul-azim-zaher',
                'image'        => 'qari-images/abdul-azim-zaher.jpg',
                'biography_ar' => 'الشيخ عبد العظيم زاهر (1904–1971) أحد الأعمدة الأولى للإذاعة المصرية، يتميز صوته بالعمق المؤثر والرقة الأخّاذة، وكان يحظى باحترام واسع من أقرانه وزملائه على تقواه الشديدة وأدائه العاطفي الصادق. يمثل ذلك النموذج الرفيع للقارئ الذي يجمع بين العلم والورع والموهبة الصوتية في آنٍ واحد.',
                'biography_en' => 'Sheikh Abdul Azim Zaher (1904–1971) was one of the early pillars of Egyptian radio, his voice deeply moving and gentle. He was universally respected by his peers for his intense piety and emotional delivery, representing the noble model of the reciter who combines scholarship, devoutness, and vocal talent in equal measure.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'محمد الصيفي',
                'name_en'      => 'Muhammad Al-Saifi',
                'slug'         => 'muhammad-al-saifi',
                'image'        => 'qari-images/muhammad-al-saifi.jpg',
                'biography_ar' => 'الشيخ محمد الصيفي (1885–1955) عُرف في أوج مجده بـ"قارئ القاهرة"، وكان شخصية موقّرة صاحبة سلطة فنية لا تُنازَع في بواكير عصر التسجيل القرآني. أسهم إسهاماً جوهرياً في تأسيس المعايير البنيوية لأسلوب التلاوة القرآنية المُسجَّلة، وتُعدّ تسجيلاته الأولى وثائق تاريخية نفيسة توثّق ميلاد هذا الفن.',
                'biography_en' => 'Sheikh Muhammad Al-Saifi (1885–1955) was known during his prime as the "Qari of Cairo." An authoritative, commanding figure in the early days of recorded Quran, he made a fundamental contribution to establishing structural standards for the recorded Quranic recitation style. His early recordings are valuable historical documents that chronicle the birth of this art form.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'منصور الشامي الدمنهوري',
                'name_en'      => 'Mansour Al-Shami Al-Damanhouri',
                'slug'         => 'mansour-al-shami-al-damanhouri',
                'image'        => 'qari-images/mansour-al-shami-al-damanhouri.jpg',
                'biography_ar' => 'الشيخ منصور الشامي الدمنهوري (1906–1959) أستاذ كلاسيكي يتميز بطابعه الصوتي المصري التقليدي العميق الأصالة. تُدرَّس تلاوته المجوّدة على نطاق واسع لما تنطوي عليه من التزام صارم بإيقاع المشايخ القدامى وألحانهم الموروثة، مما يجعله مرجعاً أساسياً لكل من يسعى إلى استيعاب روح الأسلوب المجوّد في عصره الكلاسيكي.',
                'biography_en' => 'Sheikh Mansour Al-Shami Al-Damanhouri (1906–1959) was a classical master with a highly distinct, deeply traditional Egyptian vocal flavor. His Mujawwad is heavily studied for its strict adherence to old-school pacing and melody, making him an essential reference for anyone seeking to understand the spirit of Mujawwad recitation in its classical era.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
            [
                'name_ar'      => 'محمود عبد الحكم',
                'name_en'      => 'Mahmoud Abdul Hakam',
                'slug'         => 'mahmoud-abdul-hakam',
                'image'        => null,
                'biography_ar' => 'الشيخ محمود عبد الحكم (1915–1982) قارئ مصري يتميز بأسلوب مجوّد رصين وادع وهادئ بامتياز. في حقبة كانت تسودها الأصوات الجهورية المدوّية، برز أسلوبه الناعم الثابت المطمئن بوصفه حضوراً فريداً من نوعه يعكس أصالة روحية لا تُخطئها الأذن. يُجسّد نموذجاً أصيلاً للتلاوة المجوّدة التي تلتمس قلب المستمع بالرفق لا بالقوة.',
                'biography_en' => 'Sheikh Mahmoud Abdul Hakam (1915–1982) was known for a very serene, dignified, and calm Mujawwad style. In an era dominated by incredibly loud and powerful voices, his smooth, grounded approach stood out as uniquely peaceful, reflecting a spiritual authenticity unmistakable to the ear. He embodies an authentic model of Mujawwad recitation that reaches the listener\'s heart through gentleness rather than force.',
                'is_featured'  => false,
                'status'       => 'active',
            ],
        ];

        $newSlugs = array_column($qaris, 'slug');
        Qari::whereNotIn('slug', $newSlugs)->each(function (Qari $qari) {
            $qari->tilawat()->delete();
            $qari->delete();
        });

        foreach ($qaris as $data) {
            Qari::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['created_by' => $admin->id])
            );
        }
    }
}
