<?php
//    Randonneuring.org Website Software
//    Copyright (C) 2023 Chris Nadovich
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU Affero General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU Affero General Public License for more details.
//
//    https://randonneuring.org/LICENSE.txt
//
//    You should have received a copy of the GNU Affero General Public License
//    along with this program.  If not, see <https://www.gnu.org/licenses/>.

namespace App\Libraries;

class Crypto
{

    public function make_start_code($d, $rider_id, $epp_secret)
    {
        extract($d);
        $plaintext = "$cue_version-$event_code-$rider_id-$epp_secret";
        $code = strtoupper(substr(hash('sha256', $plaintext), 0, 4));
        $code = str_replace(['0', '1'], ['X', 'Y'], $code);
        return $code;
    }

    public function make_checkin_code($d,$epp_secret){
		return $this->word_text($d,$epp_secret);
		}


    public function old_make_checkin_code($d, $epp_secret)
    {
        extract($d);
        $plaintext = "$control_index-$event_code-$rider_id-$epp_secret";
        $ciphertext = hash('sha256', $plaintext);
        $plain_code = strtoupper(substr($ciphertext, 0, 4));
        $xycode = str_replace(['0', '1'], ['X', 'Y'], $plain_code);
        return $xycode;
    }

    public function make_finish_code($d, $epp_secret)
    {
        extract($d);
        $plaintext = "Finished:$elapsed_hhmm-$global_event_id-$rider_id-$epp_secret";
        $ciphertext = hash('sha256', $plaintext);
        $plain_code = strtoupper(substr($ciphertext, 0, 4));
        $xycode = str_replace(['0', '1'], ['X', 'Y'], $plain_code);
        return $xycode;
    }

    private function word_text($d,$epp_secret) {
		extract($d);
        $plaintext = "$control_index-$event_code-$rider_id-$epp_secret";
        $ciphertext = hash('sha256', $plaintext);
    $firstEight = substr($ciphertext,0,8);
    $cipherInt = hexdec($firstEight);
    $nounIndex = $cipherInt % count($this->nouns);
    $adjIndex = $this->my_intdiv($cipherInt, count($this->nouns)) % count($this->adjectives);
    return $this->adjectives[$adjIndex] . ' ' . $this->nouns[$nounIndex];
  }
  
  private function my_intdiv($a, $b){
    return ($a - $a % $b) / $b;
}
	

  public $adjectives = [
    'able',
    'absurd',
    'active',
    'afraid',
    'agreeable',
    'alert',
    'alive',
    'amused',
    'angry',
    'annoyed',
    'anxious',
    'arrogant',
    'ashamed',
    'attractive',
    'average',
    'awful',
    'bad',
    'beautiful',
    'better',
    'bewildered',
    'black',
    'bloody',
    'blue',
    'blue-eyed',
    'blushing',
    'bored',
    'brainy',
    'brave',
    'breakable',
    'bright',
    'busy',
    'calm',
    'careful',
    'cautious',
    'charming',
    'cheerful',
    'clean',
    'clear',
    'clever',
    'cloudy',
    'clumsy',
    'colorful',
    'combative',
    'comfortable',
    'concerned',
    'condemned',
    'confused',
    'cooperative',
    'courageous',
    'crazy',
    'creepy',
    'crowded',
    'cruel',
    'curious',
    'cute',
    'dangerous',
    'dark',
    'dead',
    'defeated',
    'defiant',
    'delightful',
    'depressed',
    'determined',
    'different',
    'difficult',
    'disgusted',
    'distinct',
    'disturbed',
    'dizzy',
    'doubtful',
    'drab',
    'dull',
    'eager',
    'easy',
    'elated',
    'elegant',
    'embarrassed',
    'enchanting',
    'encouraging',
    'energetic',
    'enthusiastic',
    'envious',
    'evil',
    'excited',
    'expensive',
    'exuberant',
    'fair',
    'faithful',
    'famous',
    'fancy',
    'fantastic',
    'fierce',
    'filthy',
    'fine',
    'foolish',
    'fragile',
    'frail',
    'frantic',
    'friendly',
    'frightened',
    'funny',
    'gentle',
    'gifted',
    'glamorous',
    'gleaming',
    'glorious',
    'good',
    'gorgeous',
    'graceful',
    'grieving',
    'grotesque',
    'grumpy',
    'handsome',
    'happy',
    'healthy',
    'helpful',
    'helpless',
    'hilarious',
    'homeless',
    'homely',
    'horrible',
    'hungry',
    'hurt',
    'ill',
    'important',
    'impossible',
    'inexpensive',
    'innocent',
    'inquisitive',
    'itchy',
    'jealous',
    'jittery',
    'jolly',
    'joyous',
    'juicy',
    'kind',
    'lackadaisical',
    'large',
    'lazy',
    'light',
    'lively',
    'lonely',
    'long',
    'lovely',
    'lucky',
    'magnificent',
    'misty',
    'modern',
    'motionless',
    'muddy',
    'mushy',
    'mysterious',
    'nasty',
    'naughty',
    'nervous',
    'nice',
    'nutty',
    'obedient',
    'obnoxious',
    'odd',
    'old-fashioned',
    'open',
    'outrageous',
    'outstanding',
    'panicky',
    'perfect',
    'plain',
    'pleasant',
    'poised',
    'poor',
    'powerful',
    'precious',
    'prickly',
    'proud',
    'puzzled',
    'quaint',
    'quizzical',
    'rambunctious',
    'real',
    'relieved',
    'repulsive',
    'rich',
    'scary',
    'selfish',
    'shiny',
    'shy',
    'silly',
    'sleepy',
    'smiling',
    'smoggy',
    'sore',
    'sparkling',
    'splendid',
    'spotless',
    'stormy',
    'strange',
    'stupid',
    'successful',
    'super',
    'talented',
    'tame',
    'tender',
    'tense',
    'terrible',
    'testy',
    'thankful',
    'thoughtful',
    'thoughtless',
    'tired',
    'tough',
    'troubled',
    'ugliest',
    'ugly',
    'uninterested',
    'unsightly',
    'unusual',
    'upset',
    'uptight',
    'vast',
    'victorious',
    'vivacious',
    'wandering',
    'weary',
    'wicked',
    'wide-eyed',
    'wild',
    'witty',
    'worrisome',
    'worried',
    'wrong',
    'zany',
    'zealous'
  ];

  public $nouns = [
    'apple', 'banana', 'cherry', 'dog', 'elephant', 'fish', 'grape', 'horse',
    'ice cream', 'jazz',
    'kangaroo', 'lemon', 'mango', 'noodle', 'orange', 'pear', 'quilt', 'rabbit',
    'sunset', 'turtle',
    'umbrella', 'violin', 'watermelon', 'xylophone', 'yellow', 'zebra',
    'airplane', 'ball', 'car', 'desk',
    'egg', 'flower', 'guitar', 'hat', 'island', 'jacket', 'kite', 'lamp',
    'moon', 'notebook',
    'ocean', 'piano', 'queen', 'rose', 'ship', 'train', 'umbrella', 'volcano',
    'wallet', 'xylophone',
    'yacht', 'zeppelin', 'apple', 'banana', 'cherry', 'dog', 'elephant', 'fish',
    'grape', 'horse', 'ice cream',
    'jazz', 'kangaroo', 'lemon', 'mango', 'noodle', 'orange', 'pear', 'quilt',
    'rabbit', 'sunset',
    'turtle', 'umbrella', 'violin', 'watermelon', 'xylophone', 'yellow',
    'zebra', 'airplane', 'ball', 'car',
    'desk', 'egg', 'flower', 'guitar', 'hat', 'island', 'jacket', 'kite',
    'lamp', 'moon',
    'notebook', 'ocean', 'piano', 'queen', 'rose', 'ship', 'train', 'umbrella',
    'volcano', 'wallet',
    'xylophone', 'yacht', 'zeppelin', 'apple', 'banana', 'cherry', 'dog',
    'elephant', 'fish', 'grape',
    'horse', 'ice cream', 'jazz', 'kangaroo', 'lemon', 'mango', 'noodle',
    'orange', 'pear', 'quilt',
    'rabbit', 'sunset', 'turtle', 'umbrella', 'violin', 'watermelon',
    'xylophone', 'yellow', 'zebra',
    'airplane', 'ball', 'car', 'desk', 'egg', 'flower', 'guitar', 'hat',
    'island', 'jacket',
    'kite', 'lamp', 'moon', 'notebook', 'ocean', 'piano', 'queen', 'rose',
    'ship', 'train',
    'umbrella', 'volcano', 'wallet', 'xylophone', 'yacht', 'zeppelin', 'apple',
    'banana', 'cherry', 'dog',
    'elephant', 'fish', 'grape', 'horse', 'ice cream', 'jazz', 'kangaroo',
    'lemon', 'mango', 'noodle',
    'orange', 'pear', 'quilt', 'rabbit', 'sunset', 'turtle', 'umbrella',
    'violin', 'watermelon', 'xylophone',
    'yellow', 'zebra', 'airplane', 'ball', 'car', 'desk', 'egg', 'flower',
    'guitar', 'hat', 'island',
    'jacket', 'kite', 'lamp', 'moon', 'notebook', 'ocean', 'piano', 'queen',
    'rose', 'ship',
    'train', 'umbrella', 'volcano', 'wallet', 'xylophone', 'yacht', 'zeppelin',
    'apple', 'banana', 'cherry',
    'dog', 'elephant', 'fish', 'grape', 'horse', 'ice cream', 'jazz',
    'kangaroo', 'lemon', 'mango',
    'noodle', 'orange', 'pear', 'quilt', 'rabbit', 'sunset', 'turtle',
    'umbrella', 'violin', 'watermelon',
    'xylophone', 'yellow', 'zebra', 'airplane', 'ball', 'car', 'desk', 'egg',
    'flower', 'guitar',
    'hat', 'island', 'jacket', 'kite', 'lamp', 'moon', 'notebook', 'ocean',
    'piano', 'queen',
    'rose', 'ship', 'train', 'umbrella', 'volcano', 'wallet', 'xylophone',
    'yacht', 'zeppelin'
    // Add more nouns as needed
  ];



}

?>