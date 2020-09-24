<?php

//实现哈希表

class HashTableNode{
    public $key;
    public $val;
    public $nextNode;

    public function __construct($key,$val,$nextNode = null)
    {
        $this->key = $key;
        $this->val = $val;
        $this->nextNode = $nextNode;
    }
}


class HashTable{
    /**
     * 默认存储在hash表0上，表1用于扩容和缩容,会重新rehash，最后复制给表0，重置表1
     * @var array  ht 属性
     * table 表
     * used 总节点数
     * size 总数据量  初始化已存总数据大小，包含每个节点下的所有val
     * capacity 总容量  初始化容量16  2的幂次
     * threshold 临界值  当size超过临界值就会自动扩容 $loadFactor*$capacity
     * loadFactor 界限
     */
    private $ht = [
        [
            "size" => 0,
            "used" => 0,
            "threshold" => 0,
            "loadFactor" => 0.75,
            "capacity" => 1<<4
        ],
    ];
    private $htsStatus = false;  //扩容或者缩容状态
    private $rehashIdx = -1;   //当rehashidx为-1时表示不进行rehash，当rehashidx值为0时，表示开始进行rehash，每次对字典的添加、删除、查找、或更新操作时，都会判断是否正在进行rehash操作，如果是，则顺带进行单步rehash，并将rehashidx+1
    const MAXIMUM_CAPACITY = 12345;  //最大容量
    const MINIMUM_CAPACITY = 16;  //最小容量
    /**hash参数**/
    const M = 0;
    const R = 24;
    const SEED = 97;

    public function __construct($capacity = 16)
    {
        //初始化容量
        $this->ht[0]["capacity"] = $this->capacityInit($capacity);
        $this->ht[0]["table"] = new SplFixedArray($this->ht[0]["capacity"]);
    }

    public function get($key){
        $this->exec();
        $hash = $this->htHash($key) % $this->ht[0]["capacity"];
        $current = $this->ht[0]["table"][$hash];

        while (!empty($current)){
            if($current->key == $key){
                return $current->val;
            }
            $current = $current->nextNode;
        }
        return null;
    }

    public function set($key,$val){
        $this->exec();
        $hash = $this->htHash($key) % $this->ht[0]["capacity"];
        //var_dump($hash);
        $table = $this->ht[0]["table"];
        if(isset($table[$hash])){
            $newNode = new HashTableNode($key,$val,$table[$hash]);
        }else{
            $newNode = new HashTableNode($key,$val);
            $this->ht[0]["used"] ++ ;
        }
        $this->ht[0]["size"] ++;
        $this->ht[0]["table"][$hash] = $newNode;
        return true;
    }

    public function del($key){
        $this->exec();
        $hash = $this->htHash($key) % $this->ht[0]["capacity"];
        $current = $this->ht[0]["table"][$hash];

        $newNode = null;
        while (!empty($current)){
            if($current->key != $key){
                if(!empty($newNode)){
                    $newNode = new HashTableNode($current->key,$current->val,$newNode);
                }else{
                    $newNode = new HashTableNode($current->key,$current->val);
                }
            }
            $current = $current->nextNode;
        }
        $this->ht[0]["table"][$hash] = $newNode;
        if(empty($newNode)){
            $this->ht[0]["used"] --;
        }
        $this->ht[0]["size"] --;
        return true;
    }

    public function getSize(){
        return $this->ht[0]["size"];
    }

    public function getTable(){
        return $this->ht[0]["table"];
    }

    private function htHash($key){
        return $this->murMurHash($key);
    }

    public function getList(){
        return $this->ht[0]["table"];
    }

    private function hash($key){
        $len = strlen($key);

        $ascii = 0;
        $seed = 31;
        for ($i = 0;$i<$len;$i++){
            $key_ = ord($key[$i])+1;
            $ascii = $ascii*$seed + $key_;
        }
        $hashKey = $ascii % $this->ht[0]["capacity"];
        return $hashKey;
    }

    private function murMurHash($key,$seed = 0){
        $key  = array_values(unpack('C*', $key));
        $klen = count($key);
        $h1   = $seed < 0 ? -$seed : $seed;
        $remainder = $i = 0;
        for ($bytes=$klen-($remainder=$klen&3) ; $i < $bytes ; ) {
            $k1 = $key[$i]
                | ($key[++$i] << 8)
                | ($key[++$i] << 16)
                | ($key[++$i] << 24);
            ++$i;
            $k1  = (((($k1 & 0xffff) * 0xcc9e2d51) + ((((($k1 >= 0 ? $k1 >> 16 : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0xcc9e2d51) & 0xffff) << 16))) & 0xffffffff;
            $k1  = $k1 << 15 | ($k1 >= 0 ? $k1 >> 17 : (($k1 & 0x7fffffff) >> 17) | 0x4000);
            $k1  = (((($k1 & 0xffff) * 0x1b873593) + ((((($k1 >= 0 ? $k1 >> 16 : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0x1b873593) & 0xffff) << 16))) & 0xffffffff;
            $h1 ^= $k1;
            $h1  = $h1 << 13 | ($h1 >= 0 ? $h1 >> 19 : (($h1 & 0x7fffffff) >> 19) | 0x1000);
            $h1b = (((($h1 & 0xffff) * 5) + ((((($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000)) * 5) & 0xffff) << 16))) & 0xffffffff;
            $h1  = ((($h1b & 0xffff) + 0x6b64) + ((((($h1b >= 0 ? $h1b >> 16 : (($h1b & 0x7fffffff) >> 16) | 0x8000)) + 0xe654) & 0xffff) << 16));
        }
        $k1 = 0;
        switch ($remainder) {
            case 3: $k1 ^= $key[$i + 2] << 16;
            case 2: $k1 ^= $key[$i + 1] << 8;
            case 1: $k1 ^= $key[$i];
                $k1  = ((($k1 & 0xffff) * 0xcc9e2d51) + ((((($k1 >= 0 ? $k1 >> 16 : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0xcc9e2d51) & 0xffff) << 16)) & 0xffffffff;
                $k1  = $k1 << 15 | ($k1 >= 0 ? $k1 >> 17 : (($k1 & 0x7fffffff) >> 17) | 0x4000);
                $k1  = ((($k1 & 0xffff) * 0x1b873593) + ((((($k1 >= 0 ? $k1 >> 16 : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0x1b873593) & 0xffff) << 16)) & 0xffffffff;
                $h1 ^= $k1;
        }
        $h1 ^= $klen;
        $h1 ^= ($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000);
        $h1  = ((($h1 & 0xffff) * 0x85ebca6b) + ((((($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000)) * 0x85ebca6b) & 0xffff) << 16)) & 0xffffffff;
        $h1 ^= ($h1 >= 0 ? $h1 >> 13 : (($h1 & 0x7fffffff) >> 13) | 0x40000);
        $h1  = (((($h1 & 0xffff) * 0xc2b2ae35) + ((((($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000)) * 0xc2b2ae35) & 0xffff) << 16))) & 0xffffffff;
        $h1 ^= ($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000);
        return $h1;
    }

    /**  times 33 **/
    private function DJBHash($key){
        $hash = 5381;
        $len = strlen($key);

        $i = 0;
        while ($i<$len){
            $ascii = ord($key[$i]);
            $ascii++;
            $hash = (($hash << 5 ) +$hash) + $ascii;
            $i ++;
        }
        $hash &= ~(1 << 31);
        return $hash;
    }

    /**增删改查共同需要经过方法**/
    private function exec(){
        $this->expansionInspect();
        $this->shrinkageInspect();
    }

    /**检查扩容 超过总容量的3/4**/
    private function expansionInspect(){
        $this->ht[0]["threshold"] = $this->ht[0]["loadFactor"] * $this->ht[0]["capacity"];
        if($this->ht[0]["threshold"] < $this->ht[0]["size"] && $this->rehashIdx == -1){
            $this->resetHt(1);
            $this->ht[1]["capacity"] = $this->ht[0]["capacity"] << 2; //扩容为原来的4倍
            $this->ht[1]["table"] = new SplFixedArray($this->ht[1]["capacity"]);
            $this->rehashIdx = 0;
        }
        if($this->rehashIdx != -1){
            echo "开始扩容".PHP_EOL;
            while ($this->rehash(1) == 1){

            }
            echo "结束扩容,容量：".$this->ht[0]["capacity"].PHP_EOL;
        }
    }

    /**检查缩容  小于总容量的1/8**/
    private function shrinkageInspect(){
        $this->ht[0]["threshold"] = ((1-$this->ht[0]["loadFactor"]) / 2 ) * $this->ht[0]["capacity"];
        if($this->ht[0]["size"] > self::MINIMUM_CAPACITY && $this->ht[0]["threshold"] >= $this->ht[0]["size"] && $this->rehashIdx == -1){
            $this->resetHt(1);
            $this->ht[1]["capacity"] = $this->ht[0]["capacity"] >> 1; //缩小为原来的2倍
            $this->ht[1]["table"] = new SplFixedArray($this->ht[1]["capacity"]);
            $this->rehashIdx = 0;
        }
        if($this->rehashIdx != -1) {
            echo "开始缩容" . PHP_EOL;
            while ($this->rehash(1) == 1) {

            }
            echo "结束缩容,容量：".$this->ht[0]["capacity"] . PHP_EOL;
        }
    }

    /**初始化容量**/
    //$n 的2倍
    private function capacityInit($n){
        $n |= $n >> 1;

        $n |= $n >> 2;

        $n |= $n >> 4;

        $n |= $n >> 8;

        $n |= $n >> 16;

        return ($n < 0) ? 1 : ($n >= self::MAXIMUM_CAPACITY) ? self::MAXIMUM_CAPACITY : $n + 1;
    }

    /**重新rehash 到新表**/
    /**
     * rehash :
     * @param $n  进行n步rehash
     * @return int
     * @throws Exception
     ** created by zhangjian at 2020/9/24 13:24
     */
    private function rehash($n) :int {
        if($this->rehashIdx == -1){
            return 0;
        }

        $empty_visits = $n*10;
        while ($n-- && $this->ht[0]["size"] !=0){   //n步rehash，且ht[0]上还有正在使用的节点
            //确保rehashIdx小于ht[0]总数据量
            if($this->rehashIdx >= $this->ht[0]["capacity"]){
                throw new Exception("rehashIdx 超出范围");
            }
            //ht[0]表有些节点是空的，rehashIdx可以直接向上加，不需要移除到ht[1]表，但是保证rehashIdx递增的空间不超过$empty_visits 10的倍数
            while ($this->ht[0]["table"][$this->rehashIdx] == NULL){
                $this->rehashIdx ++ ;
                $empty_visits -- ;
                if($empty_visits == 0){
                    return 1;
                }
                //当最后一个是null，终止循环
                if($this->rehashIdx >= ($this->ht[0]["capacity"]-1)){
                    $this->rehashIdx = $this->ht[0]["capacity"]-1;
                    break;
                }
            }

            if(!empty($this->ht[0]["table"][$this->rehashIdx])){
                //获取ht[0]的某个节点
                $nodes = $this->ht[0]["table"][$this->rehashIdx];
                while (!empty($nodes)){
                    //先备份下一个地址
                    $nextNodes = $nodes->nextNode;

                    //重置结构，将节点上为null的key清除
                    $hashKey = $this->htHash($nodes->key);
                    $newIndex = $hashKey % $this->ht[1]["capacity"];
                    //移入ht[1]新节点
                    if(isset($this->ht[1]["table"][$newIndex])){
                        $newNode = new HashTableNode($nodes->key,$nodes->val,$this->ht[1]["table"][$newIndex]);
                    }else{
                        $newNode = new HashTableNode($nodes->key,$nodes->val);
                    }
                    $this->ht[1]["table"][$newIndex] = $newNode;
                    $this->ht[1]["size"] ++;
                    $this->ht[0]["size"] --;

                    //指向该节点的下一个地址
                    $nodes = $nextNodes;
                }

                //移除ht[0]节点
                $this->ht[0]["table"][$this->rehashIdx] = NULL;

                //更新ht[0]ht[1]的总节点数
                $this->ht[0]["used"] --;
                $this->ht[1]["used"] ++;

                $this->rehashIdx++;
            }


            //当ht[0]size等于0，即移除完毕
            if($this->ht[0]["size"] == 0){
                //释放ht[0]表
                unset($this->ht[0]);
                $this->ht[0] = $this->ht[1];
                //重置ht[1]
                $this->resetHt(1);

                //重置rehashIdx
                $this->rehashIdx = -1;

                return 0;//迁移完毕
            }
        }

        return 1;  //1表示还有节点需要迁移
    }

    /**
     * resetHt : 重置hash表
     * @param int $htIndex
     ** created by zhangjian at 2020/9/24 11:03
     */
    private function resetHt($htIndex){
        unset($this->ht[$htIndex]);
        $this->ht[$htIndex] =  [
            "size" => 0,
            "used" => 0,
            "threshold" => 0,
            "loadFactor" => 0.75,
            "capacity" => 1<<4
        ];
    }


}

$hash = new HashTable();

for ($i = 0;$i<98;$i++){
    $hash->set("key".$i,"val".$i);
}
var_dump($hash->getSize());
for ($i = 4;$i<90;$i++){
    $hash->del("key".$i,"val".$i);
}
var_dump($hash->getSize());
var_dump($hash->get("key2"));
var_dump($hash->get("key91"));

var_dump($hash->getTable());
