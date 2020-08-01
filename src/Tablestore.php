<?php
declare (strict_types = 1);

namespace tlingc\think\session\driver;

use think\contract\SessionHandlerInterface;

use Aliyun\OTS\OTSClient;
use Aliyun\OTS\Consts\ColumnTypeConst;
use Aliyun\OTS\Consts\RowExistenceExpectationConst;

/**
 * Session 阿里云表格存储Tablestore驱动
 */
class Tablestore implements SessionHandlerInterface
{
    protected $handler = null;

    protected $config  = [
        // 表格存储实例访问地址
        'endpoint'           => '',
        // 阿里云access key id
        'access_key_id'      => '',
        // 阿里云access key secret
        'access_key_secret'  => '',
        // 表格存储实例名称
        'instance_name'      => '',
        // 表格存储数据表名
        'table_name'         => '',
        // Session有效期 默认为3600秒（1小时）
        'expire'             => 3600,
        // Session前缀
        'prefix'             => '',
        // 启用gzip压缩
        'data_compress'      => false,
        // 连接超时时间
        'connection_timeout' => 2.0,
        // socket超时时间
        'socket_timeout'     => 2.0,
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        $this->init();
    }

    /**
     * 打开Session
     * @access protected
     * @return bool
     */
    protected function init(): bool
    {
        $this->handler = new OTSClient([
            'EndPoint' => $this->config['endpoint'],
            'AccessKeyID' => $this->config['access_key_id'],
            'AccessKeySecret' => $this->config['access_key_secret'],
            'InstanceName' => $this->config['instance_name'],
            'DebugLogHandler' => '',
            'ErrorLogHandler' => '',
            'ConnectionTimeout' => $this->config['connection_timeout'],
            'SocketTimeout' => $this->config['socket_timeout']
        ]);

        return true;
    }

    /**
     * 读取Session
     * @access public
     * @param  string $sessID
     * @return string
     */
    public function read(string $sessID): string
    {
        $content = $this->handler->getRow([
            'table_name' => $this->config['table_name'],
            'primary_key' => [
                ['key', $this->config['prefix'] . $sessID]
            ],
            'max_versions' => 1
        ]);

        if (false == empty($content['attribute_columns'])) {
            $timestamp = 0;
            $columns = [];
            foreach ($content['attribute_columns'] as $item) {
                $columns[$item[0]] = $item[1];
                if ($item[0] == 'value') {
                    $timestamp = $item[3];
                }
            }

            $expire = (int) $columns['expire'];
            if (0 != $expire && getMicroTime() > $timestamp + ($expire * 1000)) {
                //Session过期删除
                $this->delete($this->config['prefix'] . $sessID);
                return '';
            }

            if ($this->config['data_compress'] && function_exists('gzcompress')) {
                //启用数据压缩
                $columns['value'] = gzuncompress($columns['value']);
            }

            return $columns['value'];
        }

        return '';
    }

    /**
     * 写入Session
     * @access public
     * @param  string $sessID
     * @param  string $data
     * @return bool
     */
    public function write(string $sessID, string $data): bool
    {
        $data_type = ColumnTypeConst::CONST_STRING;
        $timestamp = getMicroTime();

        if ($this->config['data_compress'] && function_exists('gzcompress')) {
            //数据压缩
            $data = gzcompress($data, 3);
            $data_type = ColumnTypeConst::CONST_BINARY;
        }

        $result = $this->handler->putRow([
            'table_name' => $this->config['table_name'],
            'primary_key' => [
                ['key', $this->config['prefix'] . $sessID]
            ],
            'attribute_columns' => [
                ['value', $data, $data_type, $timestamp],
                ['expire', $this->config['expire'], ColumnTypeConst::CONST_INTEGER, $timestamp]
            ]
        ]);
        return true;
    }

    /**
     * 删除Session
     * @access public
     * @param  string $sessID
     * @return bool
     */
    public function delete(string $sessID): bool
    {
       $this->handler->deleteRow([
            'table_name' => $this->config['table_name'],
            'condition' => RowExistenceExpectationConst::CONST_IGNORE,
            'primary_key' => [
                ['key', $this->config['prefix'] . $sessID]
            ]
        ]);
		return true;
    }
}
